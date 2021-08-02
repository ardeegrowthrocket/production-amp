<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


if($installer->getAttribute('catalog_product', 'freight_class')) {
    $installer->updateAttribute('catalog_product', 'freight_class', array('source_model' =>
        'shipperhq_shipper/source_freight_freightclass'));
}

$installer->endSetup();
