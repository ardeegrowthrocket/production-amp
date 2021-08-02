<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if($installer->getAttribute('catalog_product', 'freight_class')) {
    $installer->updateAttribute('catalog_product', 'freight_class', array('frontend_input' => 'select', 'backend_type' => 'int',
        'source_model' => 'shipperhq_shipper/source_freight_freightclass'));
}
else {
    $installer->run("

        select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

        select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='Shipping' and attribute_set_id = @attribute_set_id;

        /* freight class */
        insert ignore into {$this->getTable('eav_attribute')}
            set entity_type_id 	= @entity_type_id,
            attribute_code 	= 'freight_class',
            backend_type	= 'varchar',
            frontend_input	= 'select',
            source_model          = 'shipperhq_shipper/source_freight_freightclass',
            is_required	= 0,
            is_user_defined	= 1,
            frontend_label	= 'Freight Class';

        select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='freight_class';

        insert ignore into {$this->getTable('catalog_eav_attribute')}
            set attribute_id 	= @attribute_id,
                is_visible 	= 1,
                used_in_product_listing	= 0,
                is_filterable_in_search	= 0;
    ");
}

if($installer->getAttribute('catalog_product', 'shipperhq_nmfc_class')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_nmfc_class',
        array('note' => 'Only required to support ABF Freight', 'frontend_label'	=> 'NMFC'
    ));
}
else {
    $installer->run("

        select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

        select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='Shipping' and attribute_set_id = @attribute_set_id;

        /* nmfc */
        insert ignore into {$this->getTable('eav_attribute')}
            set entity_type_id 	= @entity_type_id,
            attribute_code 	= 'shipperhq_nmfc_class',
            backend_type	= 'text',
            frontend_input	= 'text',
            is_required	= 0,
            is_user_defined	= 1,
            frontend_label	= 'NMFC',
            note = 'Only required to support ABF Freight';

        select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_nmfc_class';

        insert ignore into {$this->getTable('catalog_eav_attribute')}
            set attribute_id 	= @attribute_id,
                is_visible 	= 1,
                used_in_product_listing	= 0,
                is_filterable_in_search	= 0;

    ");
}

if(!$installer->getAttribute('catalog_product', 'must_ship_freight')) {
    $installer->run("

        select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

        select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='Shipping' and attribute_set_id = @attribute_set_id;

        /*Must Ship Freight*/
        insert ignore into {$this->getTable('eav_attribute')}
            set entity_type_id 	= @entity_type_id,
            attribute_code 	= 'must_ship_freight',
            backend_type	= 'int',
            frontend_input	= 'boolean',
            is_required	= 0,
            is_user_defined	= 1,
            frontend_label	= 'Must ship freight';

        select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='must_ship_freight';

        insert ignore into {$this->getTable('catalog_eav_attribute')}
            set attribute_id 	= @attribute_id,
            is_visible 	= 1,
            used_in_product_listing	= 0,
            is_filterable_in_search	= 0;

    ");
}

$installer->run("


select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='Shipping' and attribute_set_id = @attribute_set_id;

/* nmfc sub class */
insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'shipperhq_nmfc_sub',
	backend_type	= 'text',
	frontend_input	= 'text',
	is_required	= 0,
	is_user_defined	= 1,
	frontend_label	= 'NMFC Sub',
	note = 'Only required to support ABF Freight';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_nmfc_sub';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

");

if  (Mage::helper('wsalogger')->getNewVersion() > 10 ) {
    $lifeGateAttr = array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' 	=> 'ShipperHQ Liftgate Required',
        'length'  	=> '1',
        'nullable' 	=> 'false',
        'default'   => 0);

    $notifyReqdAttr = array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' 	=> 'ShipperHQ Notify Required',
        'length'  	=> '1',
        'nullable' 	=> 'false',
        'default'   => 0);

    $insideDeliveryAttr = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment'   => 'ShipperHQ Inside Delivery',
        'length'    => '1',
        'nullable'  => 'false',
        'default'   => 0);

    $destinationTypeAttr =  array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' 	=> 'ShipperHQ Address Type',
        'nullable' 	=> 'true',
    );

    $freightQuoteIdAttr =  array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' 	=> 'ShipperHQ Freight Quote Id',
        'nullable' 	=> 'true',
    );

    $isFreightRate = array(
        'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' => 'ShipperHQ Freight Rate',
        'nullable' => 'false',
        'default'  => '0'
    );

    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'liftgate_required', $lifeGateAttr );
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'notify_required',$notifyReqdAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'inside_delivery',$insideDeliveryAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'destination_type',$destinationTypeAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'freight_quote_id',$freightQuoteIdAttr);

    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'liftgate_required',$lifeGateAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'notify_required',$notifyReqdAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'inside_delivery',$insideDeliveryAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'destination_type',$destinationTypeAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'freight_quote_id',$freightQuoteIdAttr);

    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'freight_rate', $isFreightRate);


} else {

    $installer->run("

	ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD liftgate_required tinyint(1);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD notify_required tinyint(1);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD inside_delivery tinyint(1);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD destination_type varchar(30);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD freight_quote_id varchar(30);

	ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD liftgate_required tinyint(1);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD notify_required int(1);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD inside_delivery int(1);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD destination_type varchar(30);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD freight_quote_id varchar(30);

	");
}

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$freightAttributeCodes = array(
    'freight_class' => '1',
    'must_ship_freight' =>'10'
);

foreach ($attributeSetArr as $attr) {
    $attributeSetId = $attr['attribute_set_id'];

    $installer->addAttributeGroup($entityTypeId, $attributeSetId, 'Freight Shipping', '101');

    $freightAttGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Freight Shipping');

    foreach($freightAttributeCodes as $code => $sort) {
        $attributeId = $installer->getAttributeId($entityTypeId, $code);
        $installer->addAttributeToGroup($entityTypeId, $attributeSetId, $freightAttGroupId, $attributeId, $sort);
    }
};


$installer->endSetup();
