<?php
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
$installer->addAttribute('catalog_product', 'intallation_guide_pdf', array(
    'group'           => 'General',
    'label'           => 'Installation Guide PDF',
    'input'           => 'text',
    'type'            => 'varchar',
    'required'        => 0,
    'visible_on_front'=> 1,
    'filterable'      => 0,
    'searchable'      => 0,
    'comparable'      => 0,
    'user_defined'    => 1,
    'unique'          => 0,
    'is_configurable' => 0,
    'used_in_product_listing' => 1,
    'global'          => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note'            => 'Insert PDF Filename. ex. 82212765.pdf',
));
$installer->endSetup();
?>