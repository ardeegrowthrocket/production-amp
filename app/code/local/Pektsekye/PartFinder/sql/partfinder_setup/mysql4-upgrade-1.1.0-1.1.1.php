<?php

$this->startSetup();

$this->run("

DROP TABLE IF EXISTS `{$this->getTable('partfinder/restriction')}`;
CREATE TABLE `{$this->getTable('partfinder/restriction')}` ( 
  `selector_row_id` int(10) unsigned NOT NULL, 
  `product_id` int(10) unsigned NOT NULL,
	UNIQUE `PARTFINDER_RESTRICTION_SELECTOR_ROW_ID_PRODUCT_ID` (`selector_row_id`,`product_id`)                 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 

DROP TABLE IF EXISTS `{$this->getTable('partfinder/universal_product')}`;
CREATE TABLE `{$this->getTable('partfinder/universal_product')}` (  
  `product_id` int(10) unsigned NOT NULL,
	UNIQUE `PARTFINDER_UNIVERSAL_PRODUCT_PRODUCT_ID` (`product_id`)                 
) ENGINE=MyISAM DEFAULT CHARSET=utf8; 
                
");

$this->endSetup();
