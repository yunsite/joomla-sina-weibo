<?php
/**
 * @version         $Id: uninstall.com_weibo.php 225 2011-03-23 05:12:11Z leiy $
 */


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * com_weibo 的卸载程序，只删除文件，数据库保留。
 */
function com_uninstall(){

	global $mainframe;
	jimport('joomla.filesystem.folder');
	jimport('joomla.filesystem.file');

	$path = JPATH_ADMINISTRATOR .DS. 'components'  .DS. 'com_weibo';

	if( !JFolder::delete( $path ) ){
		$mainframe->enqueueMessage( JText::_('Unable to remove component directory!') );
	}

}
