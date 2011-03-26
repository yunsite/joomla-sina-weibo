<?php
/**
 * @version         $Id: install.com_weibo.php 223 2011-03-22 15:33:04Z yulei $
 */

defined('_JEXEC') or die();

// com_weibo_package 是一个用于安装程序的临时的组件，这个组件把需要安装的那些zip文件都作为自己的文件，安装于自己的目录下。
//  然后，对于这些zip文件进行安装。安装完毕后，com_weibo_package会自己删除自己

global $mainframe;

define( "PKG_NAME" , 'weibo_package' );
define( "COM_NAME" , 'com_weibo_package' );

jimport('joomla.application.component.model');
jimport('joomla.installer.installer' );
jimport('joomla.installer.helper');

// 从已经安装到com_weibo_package临时组件的文件中，找出zip文件
$files = JFolder::files(JPATH_ADMINISTRATOR.DS.'components'.DS.COM_NAME, 'zip', true, true);

$msg = array();

if ($files){
	foreach ($files as $file){ // 对于每个zip文件

		// 解压zip文件，取得安装包的相关信息
		$dest = dirname($file).DS.JFile::stripExt(basename($file));
		JArchive::extract($file, $dest);
		$package = getPackageFromFolder($dest);

		// 使用JInstaller对象
		$installer = new JInstaller();
		$installer->setOverwrite( true );

		// 安装相应的ZIP包
		if (!$installer->install($package['dir'])) {
			// 如果有错，将错误信息加入信息列表中
			$msg[] = '<span style="color:#FF0000">'.basename($file).': '.JText::sprintf('INSTALLEXT', JText::_($package['type']), ' - '.JText::_('ERROR')).'</span>';
			$result = false;
		} else {
			// 安装成功，也将成功信息加入信息列表中
			$msg[] = basename($file).': '.JText::sprintf('INSTALLEXT', JText::_($package['type']), JText::_('Success'));
			$result = true;
		}
	}
} else {
	echo '<p>Error installing!</p>';
}

$db  =& JFactory::getDBO();

// 激活微博插件
$query = "UPDATE #__plugins SET published=1 WHERE element ='weibo' and folder = 'content'";
$db->setQuery( $query );
$db->query();

// 删除自己
if (JFolder::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.COM_NAME)) {
	JFolder::delete(JPATH_ADMINISTRATOR.DS.'components'.DS.COM_NAME);
}
if (JFolder::exists(JPATH_SITE.DS.'components'.DS.COM_NAME)) {
	JFolder::delete(JPATH_SITE.DS.'components'.DS.COM_NAME);
}

$query = "DELETE FROM #__components WHERE name='".PKG_NAME."' LIMIT 1;";
$db->setQuery( $query );
if (!$result = $db->query()) {
	echo $db->stderr();
}

// 因为上面已经手工删除了自己。所以，Joomla的安装程序会报以文件的错误信息
// 因此，下面的这段程序，可以隐藏到这些信息
echo ' 
<style type="text/css">
  body dl#system-message{display:none !important;}
  dl#pkg_installer_msg {margin-bottom:10px;padding:3px 6px;background:#f9f9f9;border-top:3px solid #84A7DB;border-bottom:3px solid #84A7DB;}
  dl#pkg_installer_msg dt {font-weight:bold; font-size:1.2em;}  
  dl#pkg_installer_msg dd {
    font-weight:bold;
    margin:0pt;
    padding:2px;
    padding-left:30px;
  }
</style>
<script type="text/javascript">
window.addEvent("domready", function() {
 // move install log to top and remove joomla log
 $("pkg_installer_msg").injectBefore("system-message");
 $("system-message").remove();
});
</script>
';

ini_set( 'display_errors', 0 );
error_reporting( 0 );

if (count($msg)) {
	echo '<dl class=pkg_installer_msg';
	echo '<dt>安装结果:</dt>';
	foreach ($msg as $m) {
		echo '<dd> - '.$m.'</dd>';
	}
	echo '</dl>';
}

/**
 * 取得zip的安装包的相关信息
 **/
function getPackageFromFolder($p_dir)
{
	// 如果目录不正确，出错
	if (!is_dir($p_dir)) {
		JError::raiseWarning('SOME_ERROR_CODE', JText::_('Please enter a package directory'));
		return false;
	}

	// 取得包的类型
	$type = JInstallerHelper::detectType($p_dir);

	// 如果不能取得，出错
	if (!$type) {
		JError::raiseWarning('SOME_ERROR_CODE', JText::_('Path does not have a valid package'));
		return false;
	}

	// 设置相关的包的信息
	$package['packagefile'] = null;
	$package['extractdir'] = null;
	$package['dir'] = $p_dir;
	$package['type'] = $type;

	return $package;
}
?>