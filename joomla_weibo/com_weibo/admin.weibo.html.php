<?php
/**
 * $Id: admin.weibo.html.php 274 2011-04-27 10:21:48Z leiy $
 */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 *  本程序处理与腾讯授权的com_weibo的相关页面
 *
 */
class HTML_weibo{


	/**
	 * 这个数据显示一个页面，它自动会转入腾讯授权的页面
	 */
	function showTencentAuth(){
		$o = new MBOpenTOAuth( MB_AKEY , MB_SKEY  );
		$u =& JFactory::getURI();
        $p = $u->base();
		$keys = $o->getRequestToken($p.'/index.php?option=com_weibo&task=tencentcallback');//这是回调的URL 
		$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false,'');
		$_SESSION['keys'] = $keys;
		?>
<script>
document.location.href="<?php echo $aurl?>"
</script>
		<?php
	}

	/**
	 * 当授权成功时，显示成功的页面
	 */
	function finishedTencentAuth($last_key){
		?>
已经完成腾讯的认证，您所使用的腾讯微博用户名为“
		<?php echo $last_key['name']?>
”，你可以关闭本窗口。
		<?php
	}

	/**
	 * 当授权失败时，显示失败的页面
	 */
	function errorTencentAuth($last_key){
		?>
腾讯认证出错。
		<?php
	}
	
	/**
	 * 这个数据显示一个页面，它自动会转入新浪授权的页面
	 */
	function showSinaAuth(){
		
		$o = new WeiboOAuth( WB_AKEY , WB_SKEY  );

		$keys = $o->getRequestToken();
		$u =& JFactory::getURI();
        $p = $u->base();
		$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false
		 , $p.'/index.php?option=com_weibo&task=sinacallback');
		$_SESSION['keys'] = $keys;
		?>
<script>
document.location.href="<?php echo $aurl?>"
</script>
		<?php
	}

	/**
	 * 当授权成功时，显示成功的页面
	 */
	function finishedSinaAuth($last_key){
		?>
已经完成新浪的认证，您所使用的新浪微博用户id为“
		<?php echo $last_key['user_id'];?>
”，你可以关闭本窗口。
		<?php
	}

	/**
	 * 当授权失败时，显示失败的页面
	 */
	function errorSinatAuth($last_key){
		?>
新浪认证出错。
		<?php
	}

}
?>
