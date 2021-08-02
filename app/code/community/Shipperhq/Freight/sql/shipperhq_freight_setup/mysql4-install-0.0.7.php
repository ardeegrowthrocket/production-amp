<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if($installer->getAttribute('catalog_product', 'freight_class')) {
    $installer->updateAttribute('catalog_product', 'freight_class', array('frontend_input' => 'select', 'backend_type' => 'int',
        'source_model' => 'shipperhq_shipper/source_freight_freightclass'));
}
else {
    /* ------ freight_class -------- */
    $installer->addAttribute('catalog_product', 'freight_class', array(
        'type'                     => 'int',
        'input'                    => 'select',
        'label'                    => 'Freight Class',
        'global'                   => false,
        'visible'                  => 1,
        'required'                 => 0,
        'visible_on_front'         => 0,
        'is_html_allowed_on_front' => 0,
        'searchable'               => 0,
        'filterable'               => 0,
        'comparable'               => 0,
        'source'                    => 'shipperhq_shipper/source_freight_freightclass',
        'is_configurable'          => 0,
        'unique'                   => false,
        'used_in_product_listing'  => false
    ));
}

if($installer->getAttribute('catalog_product', 'shipperhq_nmfc_class')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_nmfc_class',
        array('note' => 'Only required to support ABF Freight', 'frontend_label'	=> 'NMFC'
        ));
}
else {
    /* ------ shipperhq_nmfc_class -------- */
    $installer->addAttribute('catalog_product', 'shipperhq_nmfc_class', array(
        'type'                     => 'text',
        'input'                    => 'text',
        'label'                    => 'NMFC',
        'global'                   => false,
        'visible'                  => 1,
        'required'                 => 0,
        'visible_on_front'         => 0,
        'is_html_allowed_on_front' => 0,
        'searchable'               => 0,
        'filterable'               => 0,
        'comparable'               => 0,
        'unique'                   => false,
        'used_in_product_listing'  => false,
        'note'                     => 'Only required to support ABF Freight'
    ));

}

if(!$installer->getAttribute('catalog_product', 'must_ship_freight')) {
    /* ------ must_ship_freight -------- */
    $installer->addAttribute('catalog_product', 'must_ship_freight', array(
        'type'                     => 'int',
        'input'                    => 'boolean',
        'label'                    => 'Must Ship Freight',
        'global'                   => false,
        'visible'                  => 1,
        'required'                 => 0,
        'visible_on_front'         => 0,
        'is_html_allowed_on_front' => 0,
        'searchable'               => 0,
        'filterable'               => 0,
        'comparable'               => 0,
        'unique'                   => false,
        'used_in_product_listing'  => false
    ));
}

/* ------ shipperhq_nmfc_sub -------- */
$installer->addAttribute('catalog_product', 'shipperhq_nmfc_sub', array(
    'type'                     => 'text',
    'input'                    => 'text',
    'label'                    => 'NMFC Sub',
    'global'                   => false,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'used_in_product_listing'  => false,
    'note'                     => 'Only required to support ABF Freight'
));

$version = Mage::helper('wsalogger')->getNewVersion();
$lifeGateAttr = array(
    'type'    	=> Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' 	=> 'ShipperHQ Liftgate Required',
    'length'  	=> '1',
    'nullable' 	=> 'true'
);

$notifyReqdAttr = array(
    'type'    	=> Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' 	=> 'ShipperHQ Notify Required',
    'length'  	=> '1',
    'nullable' 	=> 'true'
);

$insideDeliveryAttr = array(
    'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment'   => 'ShipperHQ Inside Delivery',
    'length'    => '1',
    'nullable'  => 'true'
);

$freightQuoteIdAttr =  array(
    'type'    	=> $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text',
    'comment' 	=> 'ShipperHQ Freight Quote Id',
    'nullable' 	=> 'true',
);

$isFreightRate = array(
    'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' => 'ShipperHQ Freight Rate',
    'nullable' => 'false',
    'default'  => '0'
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'liftgate_required')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'liftgate_required', $lifeGateAttr );
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'notify_required')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'notify_required',$notifyReqdAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'inside_delivery')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'inside_delivery',$insideDeliveryAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'freight_quote_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'freight_quote_id',$freightQuoteIdAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'liftgate_required')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'liftgate_required',$lifeGateAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'notify_required')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'notify_required',$notifyReqdAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'inside_delivery')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'inside_delivery',$insideDeliveryAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'freight_quote_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'freight_quote_id',$freightQuoteIdAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'freight_rate')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'freight_rate', $isFreightRate);
}

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getAllAttributeSetIds($entityTypeId);

$freightAttributeCodes = array(
    'freight_class' => '1',
    'must_ship_freight' =>'10'
);

foreach ($attributeSetArr as $attributeSetId) {

    $installer->addAttributeGroup($entityTypeId, $attributeSetId, 'Freight Shipping', '101');

    $freightAttGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Freight Shipping');

    foreach($freightAttributeCodes as $code => $sort) {
        $attributeId = $installer->getAttributeId($entityTypeId, $code);
        $installer->addAttributeToGroup($entityTypeId, $attributeSetId, $freightAttGroupId, $attributeId, $sort);
    }
};


$installer->endSetup();
