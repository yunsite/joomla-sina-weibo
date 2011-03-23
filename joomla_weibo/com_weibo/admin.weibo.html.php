<?php
/**
 * $Id: admin.weibo.html.php 222 2011-03-21 16:18:00Z yulei $
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
		$keys = $o->getRequestToken('http://localhost/joomla1.5.22/joomla/administrator/index.php?option=com_weibo&task=callback');//����������Ļص�URL
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
认证出错。
		<?php
	}
	
}
?>
