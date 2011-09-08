<?php
/**
 * $Id: admin.weibo.php 274 2011-04-27 10:21:48Z leiy $
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

$path = str_replace(DS."components".DS."com_weibo","",dirname(__FILE__));
require_once($path .DS."components".DS."com_weibo".DS."weibo.tencent.php");
require_once($path .DS."components".DS."com_weibo".DS."weibo.sina.php");
require_once($path .DS."components".DS."com_weibo".DS."admin.weibo.html.php");

// 本程序是com_weibo的主程序，用于处理腾讯微博的授权认证
$task = JRequest::getString('task');

// 程序处理以下几种请求，详见下面的说明
switch ($task){
	case 'tencentauth': // 当task=tencentauth时，将页面转向腾讯的授权页面
		HTML_weibo::showTencentAuth();
		break;
	case 'tencentcallback': // 当腾讯授权正常完成时，将转到task=callback回调
		tencentCallback();
		break;
	case 'sinaauth': // 当task=tencentauth时，将页面转向腾讯的授权页面
		HTML_weibo::showSinaAuth();
		break;
	case 'sinacallback': // 当腾讯授权正常完成时，将转到task=callback回调
		sinaCallback();
		break;
		default:
		break;
}

/**
 * 当腾讯授权正常完成时，将转到task=tencentcallback回调，这时调用这个函数
 */
function tencentCallback() {
	// 取得腾讯Auth对象
	$o = new MBOpenTOAuth( MB_AKEY , MB_SKEY , $_SESSION['keys']['oauth_token'] , $_SESSION['keys']['oauth_token_secret']  );
	
	// 获取last_key
	$last_key = $o->getAccessToken(  $_REQUEST['oauth_verifier'] ) ;
	if ( $last_key ) {
		// 如果成功取得last_key
		$db =& JFactory::getDBO();
		
		// 先将数据库中原有数据无论有无均删除
		$sql = "DELETE FROM #__weibo_auth WHERE type='tencent'";
		$db->setQuery($sql);
		$db->Query();

		// 将取得的last_key写入数据库中
		$sql = "INSERT INTO #__weibo_auth(id,oauth_token,oauth_token_secret,name,type ) VALUES ('1','$last_key[oauth_token]','$last_key[oauth_token_secret]','$last_key[name]','tencent') ";
		$db->setQuery($sql);
		$db->Query();
		
		// 显示已经成功获得授权的页面
		HTML_weibo::finishedTencentAuth($last_key);
	} else {
		// 如果未成功取得last_key，显示出错的页面
		HTML_weibo::errorTencentAuth();
	}
}

/**
 * 当新浪授权正常完成时，将转到task=sinacallback回调，这时调用这个函数
 */
function sinaCallback() {
	// 取得新浪Auth对象
	$o = new WeiboOAuth( WB_AKEY , WB_SKEY , $_SESSION['keys']['oauth_token'] , $_SESSION['keys']['oauth_token_secret']  );
		
	// 获取last_key
	$last_key = $o->getAccessToken(  $_REQUEST['oauth_verifier'] ) ;
	if ( $last_key ) {
		// 如果成功取得last_key
		$db =& JFactory::getDBO();
		
		// 先将数据库中原有数据无论有无均删除
		$sql = "DELETE FROM #__weibo_auth WHERE type='sina'";
		$db->setQuery($sql);
		$db->Query();

		// 将取得的last_key写入数据库中
		$sql = "INSERT INTO #__weibo_auth(id,oauth_token,oauth_token_secret,name,type ) VALUES ('2','$last_key[oauth_token]','$last_key[oauth_token_secret]','$last_key[user_id]', 'sina') ";
		$db->setQuery($sql);
		$db->Query();
		
		// 显示已经成功获得授权的页面
		HTML_weibo::finishedSinaAuth($last_key);
	} else {
		// 如果未成功取得last_key，显示出错的页面
		HTML_weibo::errorSinaAuth();
	}
}

?>
