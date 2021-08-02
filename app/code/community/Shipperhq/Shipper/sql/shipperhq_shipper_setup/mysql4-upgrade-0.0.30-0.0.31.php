<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/* ------ shipperhq_availability_date -------- */
$this->addAttribute('catalog_product', 'shipperhq_availability_date', array(
    'type'                     => 'datetime',
    'backend'                  => 'eav/entity_attribute_backend_datetime',
    'input'                    => 'date',
    'label'                    => 'Availability Date',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'is_configurable'          => 0,
    'unique'                   => false,
    'user_defined'			   => true,
    'used_in_product_listing'  => false
));

$installer->endSetup();
