<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


$installer->run("

  SELECT @attribute_id:=attribute_id FROM {$this->getTable('eav_attribute')} WHERE attribute_code='freight_class';

    INSERT IGNORE into {$this->getTable('catalog_product_entity_int')} (entity_type_id, attribute_id, store_id, entity_id, value)
			SELECT entity_type_id, attribute_id, store_id, entity_id, value
			FROM catalog_product_entity_text
			WHERE attribute_id = @attribute_id;

		DELETE FROM {$this->getTable('catalog_product_entity_text')} WHERE attribute_id =  @attribute_id;


  INSERT IGNORE into {$this->getTable('catalog_product_entity_int')} (entity_type_id, attribute_id, store_id, entity_id, value)
			SELECT entity_type_id, attribute_id, store_id, entity_id, value
			FROM catalog_product_entity_varchar
			WHERE attribute_id = @attribute_id;

		DELETE FROM {$this->getTable('catalog_product_entity_varchar')} WHERE attribute_id =  @attribute_id;
");


$installer->endSetup();
