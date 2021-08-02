<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if($installer->getAttribute('catalog_product', 'must_ship_freight')) {
    $installer->updateAttribute('catalog_product', 'must_ship_freight', array('note' =>
                                                                                  'Can be overridden at Carrier level within ShipperHQ'));
}

$installer->endSetup();
