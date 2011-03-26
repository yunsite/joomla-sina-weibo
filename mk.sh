#!/bin/bash

cd joomla_weibo
rm com_weibo.zip
rm plugin_weibo.zip
zip -r com_weibo.zip com_weibo -i \*.php \*.xml \*.ini
zip -r plugin_weibo.zip plugin_weibo -i \*.php \*.xml \*.ini

cd ..
rm joomla_weibo.zip
zip joomla_weibo.zip joomla_weibo/com_weibo.zip  joomla_weibo/plugin_weibo.zip joomla_weibo/install.com_weibo_package.php  joomla_weibo/weibo_package.xml
