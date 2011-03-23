<?php
/**
 * @version         $Id: uninstall.com_weibo.php 222 2011-03-21 16:18:00Z yulei $
 */


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

function com_uninstall(){

	global $mainframe;
	jimport('joomla.filesystem.folder');
	jimport('joomla.filesystem.file');

	$path = JPATH_ADMINISTRATOR .DS. 'components'  .DS. 'com_weibo';

	if( !JFolder::delete( $path ) ){
		$mainframe->enqueueMessage( JText::_('Unable to remove component directory!') );
	}

	//language/en-GB/en-GB.plg_content_version.ini

	/*$path = JPATH_ROOT .DS. 'language' .DS. 'en-GB'  .DS. 'en-GB.plg_editors-xtd_versioning.ini';

	if( !JFolder::delete( $path ) ){
	$mainframe->enqueueMessage( JText::_('Unable to remove!' . $path) );
	}*/

}
