<?php
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
$installer->addAttribute('catalog_product', 'custom_url_key', array(
    'group'           => 'General',
    'label'           => 'AutoPart URL Key',
    'input'           => 'text',
    'type'            => 'varchar',
    'required'        => 0,
    'visible_on_front'=> 1,
    'filterable'      => 0,
    'searchable'      => 0,
    'comparable'      => 0,
    'user_defined'    => 1,
    'unique'          => 1,
    'is_configurable' => 0,
    'used_in_product_listing' => 1,
    'global'          => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note'            => 'Used for /sku and /sku-ymm',
));
$installer->endSetup();
?>