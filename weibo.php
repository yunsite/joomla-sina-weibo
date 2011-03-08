<?php
/**
 * @version        $Id$
 * @package        Joomla
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$mainframe->registerEvent( 'onAfterDisplayContent', 'plgFenxiang' );
$mainframe->registerEvent( 'onAfterContentSave', 'plgWeibo' );
//$mainframe->registerEvent( 'onAfterContentSave', 'plgWeiboAfter' );

define('WEIBO_LIMIT', 140);
require_once('weibo.sina.php');

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

/*
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

/*
 * 发送微博
 */
function sendWeibo( $row, $P)
{
    // 如果没有设置微博帐户，则直接返回
    if (!$P['account'] ) {
        return true;
    }

    // 如果设置的文章的分类，则检查本文是否属于这个分类，如果不是，直接返回
    if ( $P['catid'] ) {
        if ( $row->catid != $P['catid'] ) {
            return true;
        }
    }

    

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
    
    // 因为新浪微博限制字数为140字，删去多出部分
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

    // 如果没有文字可以发表，则直接返回
    if ( $weibotext == '' ) {
        return true;
    }

    // 取得新浪微博对象
    $weibo = new weibo( '593737441' );
    $weibo->setUser( $P['account'] , $P['password'] );

    // 如果有图片，上传图片，发表有图片的微博
    if ( $imgfile ){
        $result = $weibo->upload($weibotext, $imgfile);
        $result = $weibo->update($weibotext);
    } else {
        // 发表没有图片的微博
        $result = $weibo->update($weibotext);
    }

    /*if ( isset($result["id"]) ) {
     $weiboid = 'SINAWEIBOID:'.$result["id"];
     if ( $row->fulltext ) {
     $row->fulltext .= '<!-- DONOT delete this line, '.$weiboid.' -->';
     } else {
     $row->introtext .= '<!-- DONOT delete this line, '.$weiboid.' -->';
     }
     }*/

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
    if ( $pluginParams->get( 'sinaenabled' ) ){
        $P['weibotype'] =  $pluginParams->get('weibotype'); // 微博发表方式（fulltext，onlytitle，introtext或者custom）
        $P['catid'] =  $pluginParams->get('catid'); // 所指定的分类
        $P['account'] = $pluginParams->get('account'); // 新浪微博帐户
        $P['password'] = $pluginParams->get('password'); // 新浪微博密码
        $P['customstring'] = $pluginParams->get('customstring'); // 自定义的字符串
        $P['picsend'] = $pluginParams->get('picsend'); // 将文章中的第一幅图片发布到微博上
        sendWeibo($row, $P);
    }

    return true;
}

/**
 * 准备“分享按钮”
 */
function sinaButton( $row, $P )
{
    // 取得网站的root URI
    $u =& JFactory::getURI();
    $root = $u->root();

    $weibotext = $P['fenxiangstring']?$P['fenxiangstring']:'分享 %T'; // 如果没有自己定义微博文字，则使用“分享 文章标题”
    $weibotext = str_replace('%T', $row->title, $weibotext);    // %T 替换成文章的标题
    $weibotext = str_replace('%F', $row->introtext . '<br>'. $row->fulltext, $weibotext); // %F 替换成文章的全文
    $weibotext = str_replace('%I', $row->introtext, $weibotext);  // %I 替换为引言
    $weibotext = str_replace('%H', $root, $weibotext);   // %H 替换为网站网址
    
    //因为新浪微博限制字数为140字，删去多出部分
    $weibotext = mb_substr($weibotext, 0, WEIBO_LIMIT, 'utf-8');
    
    // 清理文字
    $weibotext = cleanText($weibotext);
    
    // 准备图片上的停留时显示的提示文字
    // 一般情况下，显示用户设置的按钮显示文字，如果用户没有设置，则显示‘分享到新浪微博’
    $buttontitle = $P['buttonlabel'] ? $P['buttonlabel'] : '分享到新浪微博';
    
    // 准备按钮
    if ( $P['buttonalign'] == 'left' ) {
            $buttontext = '<td align=left>';
    } else  if ( $P['buttonalign'] == 'center' ) {
            $buttontext = '<td align=center>';
    } else {
            $buttontext = '<td align=right>';
    }
    $buttontext  .= '<a title="'.$buttontitle.'" style="color:#333333;text-align:left;font-size:12px;"';
    $buttontext  .= 'href="javascript:void((function(s,d,e){try{}catch(e){}var f=\'http://v.t.sina.com.cn/share/share.php?\',u=d.location.href,p=[\'url=\',e(u),\'&title=\',e(d.title),\'&appkey=593737441\'].join(\'\');function a(){if(!window.open([f,p].join(\'\'),\'mb\',[\'toolbar=0,status=0,resizable=1,width=620,height=450,left=\',(s.width-620)/2,\',top=\',(s.height-450)/2].join(\'\')))u.href=[f,p].join(\'\');};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})(screen,document,encodeURIComponent));">';
    $buttontext .= "<img src='".JURI::base()."plugins/content/weibo-sina.ico' />";
    if ( $P['buttonlabel'] && $P['buttonlabel'] != '空' ) {
            $buttontext  .=$P['buttonlabel'].'</a>';
    } else {
            $buttontext  .='</a>';
    }
    $buttontext  .= '</td>';
    if ( $P['buttonalign'] == 'center' ) {
         $buttontext .= '<td width=50%></td>';
    }

    return $buttontext;
} 

/**
 * 分享至新浪微博按钮插件
 */
function plgFenxiang( &$article, &$params, $limitstart  )
{
    $plugin =& JPluginHelper::getPlugin('content', 'weibo');
    $pluginParams = new JParameter( $plugin->params );

    // 如果没有启用本插件，则直接返回
    if ( $pluginParams ) {
        if (!JPluginHelper::isEnabled('content', 'weibo')) {
            return;
        }
    }

    // 如果是显示引言或者是在弹出窗口中显示的文章，则不显示分享按钮
    if ( $params->get( 'intro_only' )|| $params->get( 'popup' )  ) {
        return;
    }

    // 参数的设置
    $P['buttoncatid'] =  $pluginParams->get('buttoncatid'); // 所指定的分类
    $P['fenxiangstring'] = $pluginParams->get('fenxiangstring'); // 自定义的字符串
    $P['buttonpos'] = $pluginParams->get('buttonpos'); // 按钮位置（文章顶部或者底部）
    $P['buttonalign'] = $pluginParams->get('buttonalign'); // 按钮对齐（左，中，右）
    $P['buttonlabel'] = $pluginParams->get('buttonlabel'); // 按钮显示文字
    
    // 如果设置的文章的分类，则检查本文是否属于这个分类，如果不是，直接返回
    if ( $P['buttoncatid'] ) {
        if ( $row->catid != $P['buttoncatid'] ) {
            return;
        }
    }
    
    $sinabutton = sinaButton($article, $P );
    if ( $sinabutton) {
        if ( $P['buttonpos'] == 'bottom' ) {
            $article->text .= '<table width=100% ><tr>'.$sinabutton.'</tr></table>';
        } else {
            $article->text = '<table width=100% ><tr>'.$sinabutton.'</tr></table>' .  $article->text;
        }
    }
    
    return;
}
