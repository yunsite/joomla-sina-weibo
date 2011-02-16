<?php
/**
 * @version		$Id$
 * @package		Joomla
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$mainframe->registerEvent( 'onAfterContentSave', 'plgWeibo' );
//$mainframe->registerEvent( 'onAfterContentSave', 'plgWeiboAfter' );

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
 * 取得文章发表后的URL，当用户自定义微博文字时，如果包含有%L，则替换成文章的网址
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

	// 取得网站的root URI
	$u =& JFactory::getURI();
	$root = $u->root();

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
		$link = $root.JRoute::_(getArticleRoute($row->id, $row->catid, $row->sectionid));
		$weibotext = str_replace('%T', $row->title, $P['customstring']);
		$weibotext = str_replace('%F', $row->introtext . '<br>'. $row->fulltext, $weibotext);
		$weibotext = str_replace('%I', $row->introtext, $weibotext);
		$weibotext = str_replace('%L', $link, $weibotext);
	}

	// 检查有无图片
	$imgfile = false;
	if ( $P['picsend'] ){
		if ( preg_match('/<img\ssrc="([^"]+)"/i', $row->introtext . $row->fulltext, $matchs)){
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
 * Weibo plugin
 */
function plgWeibo( &$row, $isNew )
{
	$plugin			=& JPluginHelper::getPlugin('content', 'weibo');
	$pluginParams	= new JParameter( $plugin->params );

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