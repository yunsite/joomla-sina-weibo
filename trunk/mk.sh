#!/bin/bash

cd joomla_weibo
rm com_weibo.zip
rm plugin_weibo.zip
zip -r com_weibo.zip com_weibo -i \*.php \*.xml \*.ini
zip -r plugin_weibo.zip plugin_weibo -i \*.php \*.xml \*.ini
