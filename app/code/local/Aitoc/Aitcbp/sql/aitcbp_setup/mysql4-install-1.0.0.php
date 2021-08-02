<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();

$installer->addAttribute('catalog_product', 'cbp_group', array(
	'group'							=> 'Prices',
	'label'							=> 'Automatic Pricing Group',
	'type'							=> 'int',
	'input'							=> 'select',
	'source'						=> 'aitcbp/price_group',
	'global'						=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible'						=> true,
	'required'						=> false,
	'user_defined'					=> false,
	'default'						=> 0,
	'searchable'			        => true,
	'filterable'			        => false,
	'comparable'			        => false,
	'visible_on_front'  			=> false,
	'visible_in_advanced_search'	=> false,
	'used_in_product_listing' 		=> false,
	'unique'			            => false,
	'input_renderer'				=> 'aitcbp/adminhtml_group_renderer',
	'apply_to'						=> 'simple,configurable,virtual,downloadable,bundle',
));

$installer->updateAttribute('catalog_product', 'cost', array(
	'apply_to'						=> 'simple,configurable,virtual,downloadable,bundle',
));

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('aitoc_aitcbp_rules')} (
  `entity_id` int(10) NOT NULL AUTO_INCREMENT,
  `in_groups` text NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `conditions` text NOT NULL,
  `actions` text NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `priority` int(10) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cost Based Price Rules';

CREATE TABLE IF NOT EXISTS {$this->getTable('aitoc_aitcbp_groups')} (
  `entity_id` int(10) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `cbp_type` tinyint(1) NOT NULL,
  `amount` decimal(12,4) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cost Based Price Groups';

");

$installer->endSetup(); 
?>