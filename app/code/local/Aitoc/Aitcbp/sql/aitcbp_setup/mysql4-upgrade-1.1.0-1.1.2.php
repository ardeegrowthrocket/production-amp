<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('aitoc_aitcbp_product_price_index')} (
    `product_price_index_id` int NOT NULL AUTO_INCREMENT,
    `product_id` int NOT NULL, 
    `website_id` int NOT NULL, 
    `cbp_group` int NOT NULL, 
    `price` decimal (12, 4) NOT NULL, 
    PRIMARY KEY (`product_price_index_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cost Based Price Product Price Index';

CREATE UNIQUE INDEX UNQ_AITOC_AITCBP_PRODUCT_PRICE_INDEX 
    ON {$this->getTable('aitoc_aitcbp_product_price_index')}(`product_id`,`website_id`);

");

$installer->endSetup(); 
?>
