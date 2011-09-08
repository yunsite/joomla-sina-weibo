<?php
/**
 * @version         $Id: install.com_weibo.php 274 2011-04-27 10:21:48Z leiy $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * com_weibo 的安装程序，现在只是为了创建用于在读认证数据的数据库，
 */
function com_install()
{

	$db  =& JFactory::getDBO();

	$sql = 'CREATE TABLE IF NOT EXISTS `#__weibo_auth` (
  `id` int(11) NOT NULL,
  `oauth_token` varchar(255) NOT NULL,
  `oauth_token_secret` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(10),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
';

	$db->setQuery($sql);
	$db->query() or die("Error in $sql");

}
?>
