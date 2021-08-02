<?php
$installer = $this;
$installer->startSetup();

$sql=<<<SQLTEXT
CREATE TABLE `{$installer->getTable('gr_slider_banner')}` (
      `id` int(11) NOT NULL auto_increment,
      `title` varchar(255) NOT NULL default '',
      `image` varchar(500) NOT NULL default '',
      `is_active` int(1) NOT NULL default 0,
      `position` int(4) NOT NULL default 0,
      `body` longtext,
      `style` longtext,
      `store_ids` varchar(50) NOT NULL default '',
      `updated_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
      `created_date` datetime NULL,
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 