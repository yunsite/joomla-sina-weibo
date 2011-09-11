<?php
/**
 * @version        $Id: weibo.php 274 2011-04-27 10:21:48Z leiy $
 * @package        Joomla
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$mainframe->registerEvent( 'onAfterContentSave', 'plgWeibo' );
//$mainframe->registerEvent( 'onAfterContentSave', 'plgWeiboAfter' );

define('WEIBO_LIMIT', 140); // 限制字数
require_once('weibo.sina.php');
require_once('weibo.tencent.php');

/**
 * 清理文字
 */
function cleanText ( &$text )
{
    $text = preg_replace( "'<script[^>]*>.*?</script>'si", '', $text );
    $text = preg_replace( '/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text );
    $text = preg_replace( '/<!--.+?-->/', '', $text );
    $text = preg_replace( '/{.+?}/', '', $text );
    $text = preg_replace( '/&nbsp;/', ' ', $text );
    //$text = preg_replace( '/&amp;/', ' ', $text );
    $text = preg_replace( '/&quot;/', ' ', $text );
    $text = strip_tags( $text );
    $text = htmlspecialchars( $text );
    return $text;
}

/**
 * 取得文章发表后的URL，如果当用户自定义微博文字时，如果包含有%L，则替换成文章的网址，此网址由此函数生成
 */
function getArticleRoute($id, $catid = 0, $sectionid = 0)
{
    $needles = array(
            'article'  => (int) $id,
            'category' => (int) $catid,
            'section'  => (int) $sectionid,
    );

    //Create the link
    $link = 'index.php?option=com_content&view=article&id='. $id;

    if($catid) {
        $link .= '&catid='.$catid;
    }

    //if($item = ContentHelperRoute::_findItem($needles)) {
    //   $link .= '&Itemid='.$item->id;
    //};

    return $link;
}

/**
 * 准备微博文字
 */
function getWeiboText( $row, $P, &$weibocontent)
{
	unset ( $weibocontent['text'] );
	unset ( $weibocontent['imgfile'] );
	
	// 根据微博文字的种类
    if ( $P['weibotype'] == 'fulltext' ) {
        //  1) 全文发表
        $weibotext = $row->introtext . '<br>'. $row->fulltext;
    }else if ( $P['weibotype'] == 'introtext' ) {
        //  2) 发表引文
        $weibotext = $row->introtext ;
    }else if ( $P['weibotype'] == 'title' ) {
        //  3）发表标题
        $weibotext = $row->title;
    }else {
        //  4) 自定义发表文字
        
        // 取得网站的root URI
        $u =& JFactory::getURI();
        $root = $u->root();
        $link = JRoute::_(getArticleRoute($row->id, $row->catid, $row->sectionid), false);
        $weibotext = str_replace('%T', $row->title, $P['customstring']);    // %T 替换成文章的标题
        $weibotext = str_replace('%F', $row->introtext . '<br>'. $row->fulltext, $weibotext); // %F 替换成文章的全文
        $weibotext = str_replace('%I', $row->introtext, $weibotext);  // %I 替换为引言
        $weibotext = str_replace('%H', $root, $weibotext);   // %H 替换为网站网址
        $weibotext = str_replace('%L', $root.$link, $weibotext); // %L （ALPAH）替换成此文章的URL，此功能尚有BUG
    }
    
    // 因为微博限制字数为140字，删去多出部分
    $weibotext = mb_substr($weibotext, 0, WEIBO_LIMIT, 'utf-8'); 

    // 检查有无图片
    $imgfile = false;
    if ( $P['picsend'] ){
        if ( preg_match('/<img[^>]*src="([^"]+)"/is', $row->introtext . $row->fulltext, $matchs)){
            if ( strpos($matchs[1], 'images/') === 0 ) {
                $picurl = $root.$matchs[1];
            } else {
                $picurl = $matchs[1];
            }
            //如果有图片，取得图片数据
            $imgfile = file_get_contents( $picurl );
        } 
    }

    // 清理文字
    $weibotext = cleanText($weibotext);
    
    // 如果仅有图片，而无文字，则填充文字“无”
    if ( $imgfile && ! $weibotext) {
    	$weibotext = '无';
    } 
    
    // 保存返回
    $weibocontent['text'] = $weibotext ;
    $weibocontent['imgfile'] = $imgfile;
    $weibocontent['imgurl'] = $picurl;
	
}

/**
 * 发送新浪微博
 */
function sendSinaWeibo( $weibocontent, $P)
{
	
	// 如果没有新浪的授权，直接返回
	if ( !$P['sinalastkey']) {
		return;
	}
	$c = new WeiboClient( WB_AKEY , WB_SKEY , $P['sinalastkey']['oauth_token'] , $P['sinalastkey']['oauth_token_secret']  );
	
	// 如果有图片，上传图片，发表有图片的微博
	if ( $weibocontent['imgurl']) {
		$rtn1 = $c ->upload(  $weibocontent['text'] , $weibocontent['imgurl'] );
	} else {
		// 发表没有图片的微博
		$rtn = $c->update( $weibocontent['text'] );
	}
	
}

/**
 * 发送腾讯微博
 */
function sendTencentWeibo( $weibocontent, $P)
{
	// 如果没有腾讯的授权，直接返回
	if ( !$P['tencentlastkey']) {
		return;
	}
	
	// 准备微博对象
	$c = new MBApiClient( MB_AKEY , MB_SKEY , $P['tencentlastkey']['oauth_token'] , $P['tencentlastkey']['oauth_token_secret']  );

	// 如果有图片，上传图片，发表有图片的微博
	if ( $weibocontent['imgfile']) {
		$p =array(
			'c' => $weibocontent['text'],
			'ip' => $_SERVER['REMOTE_ADDR'], 
			'j' => '',
			'w' => '',
			'p' => array(null,'pic from joomla',$weibocontent['imgfile']),
			'type' => 0
		);
	} else {
		// 发表没有图片的微博
		$p =array(
			'c' => $weibocontent['text'],
			'ip' => $_SERVER['REMOTE_ADDR'], 
			'j' => '',
			'w' => ''
		);
	}
	$rtn = $c->postOne($p);
}

/**
 * 发表文章自动发表微博插件
 */
function plgWeibo( &$row, $isNew )
{
    $plugin            =& JPluginHelper::getPlugin('content', 'weibo');
    $pluginParams    = new JParameter( $plugin->params );

    // 只处理新建的文章，所以如果不是新文章，直接返回
    if ( !$isNew ) {
        return true;
    }

    // 如果没有启用本插件，则直接返回
    if ( $pluginParams ) {
        if (!JPluginHelper::isEnabled('content', 'weibo')) {
            return true;
        }
    }

    // 参数的设置
    $P['sinaenabled'] = $pluginParams->get( 'sinaenabled' ); //是否启用新浪微博
    if ( $P['sinaenabled']  ) {
    	// 如果启动新浪微博，则取得数据库中存储的新浪微博授权码
    	$db =& JFactory::getDBO();
		$sql = "SELECT  oauth_token, oauth_token_secret, name FROM #__weibo_auth WHERE id = '2' AND type='sina'";
		$db->setQuery( $sql );
		$result = $db->loadAssoc();
		$P['sinalastkey'] = $result;
    }
    $P['tencentenabled'] = $pluginParams->get( 'tencentenabled' ); //是否启用腾讯微博
    if ( $P['tencentenabled']  ) {
    	// 如果启动腾讯微博，则取得数据库中存储的腾讯微博授权码
    	$db =& JFactory::getDBO();
		$sql = "SELECT  oauth_token, oauth_token_secret, name FROM #__weibo_auth WHERE id = '1' AND type='tencent'";
		$db->setQuery( $sql );
		$result = $db->loadAssoc();
		$P['tencentlastkey'] = $result;
    }
    $P['weibotype'] =  $pluginParams->get('weibotype'); // 微博发表方式（fulltext，onlytitle，introtext或者custom）
    $P['catid'] =  $pluginParams->get('catid'); // 所指定的分类
    $P['customstring'] = $pluginParams->get('customstring'); // 自定义的字符串
    $P['picsend'] = $pluginParams->get('picsend'); // 将文章中的第一幅图片发布到微博上
    
    // 如果设置的文章的分类，则检查本文是否属于这个分类，如果不是，直接返回
    if ( $P['catid'] ) {
        if ( $row->catid != $P['catid'] ) {
            return true;
        }
    }

    // 准备微博文字和图片
    getWeiboText($row, $P, $weibocontent);
    
    // 发送新浪微博
    if ( $P['sinaenabled'] && $P['sinalastkey']  ){
    	sendSinaWeibo( $weibocontent, $P);
    }
    
    // 发送腾讯微博
	if ( $P['tencentenabled'] && $P['tencentlastkey'] ){
    	sendTencentWeibo( $weibocontent, $P);
    }

    return true;
}
?>
