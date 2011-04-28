<?php
/**
 * @version         $Id: install.com_weibo.php 223 2011-03-22 15:33:04Z yulei $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

global $mainframe;

jimport('joomla.application.component.model');
jimport('joomla.installer.installer' );
jimport('joomla.installer.helper');

$files = JFolder::files(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_weibo_package', 'zip', true, true);

$msg = array();

if ($files) 
{
  foreach ($files as $file){
  	
    $dest = dirname($file).DS.JFile::stripExt(basename($file));
    JArchive::extract($file, $dest);
    $package = weibo_getPackageFromFolder($dest);

    // Get an installer instance
    $installer = new JInstaller();

    // Install the package
    if (!$installer->install($package['dir'])) {
      // There was an error installing the package
      $msg[] = '<span style="color:#EC5B0E">'.basename($file).': '.JText::sprintf('INSTALLEXT', JText::_($package['type']), ' - '.JText::_('ALREADY EXISTS!')).'</span>';
      $result = false;
    } else {
      // Package installed sucessfully
      $msg[] = basename($file).': '.JText::sprintf('INSTALLEXT', JText::_($package['type']), JText::_('Success'));
      $result = true;
    }
  }
} else {
  echo '<p>Error installing!</p>';
}

$db  =& JFactory::getDBO();

// activer Plugin de versions
$query = "UPDATE #__plugins SET published=1 WHERE element ='weibo' and folder = 'content'";
$db->setQuery( $query );
$db->query();

/**
 * Effacer lesdfd composant installation com_versions_package des fichiers systemes et base de donnees
**/
if (JFolder::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_weibo_package')) {
  JFolder::delete(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_weibo_package');
} 
if (JFolder::exists(JPATH_SITE.DS.'components'.DS.'com_weibo_package')) {
  JFolder::delete(JPATH_SITE.DS.'components'.DS.'com_weibo_package');
}  

$query = "DELETE FROM #__components WHERE name='weibo_package' LIMIT 1;";
$db->setQuery( $query );
if (!$result = $db->query()) {
  echo $db->stderr();
}

/* Hide Errors to caddddn remddfove VERSIONS instaldfler component on install process and formatting install log output */
echo ' 
<style type="text/css">
  body dl#system-message{display:none !important;}
  dl#versions_installer_msg {margin-bottom:10px;padding:3px 6px;background:#f9f9f9;border-top:3px solid #84A7DB;border-bottom:3px solid #84A7DB;}
  dl#versions_installer_msg dt {font-weight:bold; font-size:1.2em;}  
  dl#versions_installer_msg dd {
    font-weight:bold;
    margin:0pt;
    padding:2px;
    padding-left:30px;
  }
</style>
<script type="text/javascript">
window.addEvent("domready", function() {
 // move install log to top and remove joomla log
 $("versions_installer_msg").injectBefore("system-message");
 $("system-message").remove();
});
</script>
';
ini_set( 'display_errors', 0 );
error_reporting( 0 );

if (count($msg)) {
  echo '<dl id="versions_installer_msg">';
  echo '<dt>Simple Content Versioning Package - Journal Installation Results:</dt>';
  foreach ($msg as $m) {
  echo '<dd> - '.$m.'</dd>';
  }
  echo '</dl>';
}

/**
 * Function to get Package informations for Joomla! installer
**/
function weibo_getPackageFromFolder($p_dir)
{
  // Did you give us a valid directory?
  if (!is_dir($p_dir)) {
    JError::raiseWarning('SOME_ERROR_CODE', JText::_('Please enter a package directory'));
    return false;
  }

  // Detect the package type
  $type = JInstallerHelper::detectType($p_dir);

  // Did you give us a valid package?
  if (!$type) {
    JError::raiseWarning('SOME_ERROR_CODE', JText::_('Path does not have a valid package'));
    return false;
  }

  $package['packagefile'] = null;
  $package['extractdir'] = null;
  $package['dir'] = $p_dir;
  $package['type'] = $type;

  return $package;
}
?>