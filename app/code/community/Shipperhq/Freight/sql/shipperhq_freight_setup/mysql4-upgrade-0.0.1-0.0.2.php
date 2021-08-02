<?php
/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

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
	note        = 'Only required to support ABF Freight';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_nmfc_sub';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

");

$installer->updateAttribute('catalog_product', 'freight_class', array('frontend_input' => 'select', 'backend_type' => 'int',
    'source_model' => 'shipperhq_shipper/source_freight_freightclass'));


$installer->updateAttribute('catalog_product', 'shipperhq_nmfc_class', array('note' => 'Only required to support ABF Freight',
    'frontend_label'	=> 'NMFC'));

$installer->updateAttribute('catalog_product', 'shipperhq_nmfc_sub', array('note' => 'Only required to support ABF Freight',
    'frontend_label'	=> 'NMFC Sub'));

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
