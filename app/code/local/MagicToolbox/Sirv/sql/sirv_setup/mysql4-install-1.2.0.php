<?php

$installer = $this;

$installer->startSetup();

$table = $installer->getTable('sirv/cache');

$installer->run("
DROP TABLE IF EXISTS `{$table}`;
CREATE TABLE `{$table}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT 'Url',
  `modification_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Modification time',
  PRIMARY KEY (`id`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Url Cache Table';
");

$installer->setConfigData('sirv/general/sirv_image_processing', '1', 'default', 0);

$installer->endSetup();
