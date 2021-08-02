<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
    SELECT @attribute_id:=attribute_id FROM {$installer->getTable('eav_attribute')} WHERE attribute_code='shipperhq_warehouse';

    INSERT into {$installer->getTable('catalog_product_entity_text')} (entity_type_id, attribute_id, store_id, entity_id, value)
        SELECT entity_type_id, attribute_id, store_id, entity_id, value
        FROM {$installer->getTable('catalog_product_entity_varchar')}
        WHERE attribute_id = @attribute_id ;

    DELETE FROM {$installer->getTable('catalog_product_entity_varchar')} WHERE attribute_id = @attribute_id;
");

$installer->endSetup();
