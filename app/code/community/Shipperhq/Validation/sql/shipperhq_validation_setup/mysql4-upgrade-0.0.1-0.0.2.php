<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


if($installer->getAttribute('customer_address', 'address_valid')) {
    $installer->updateAttribute('customer_address', 'address_valid', array('source_model' =>
        'shipperhq_shipper/source_validation_result'));
}

$installer->endSetup();
