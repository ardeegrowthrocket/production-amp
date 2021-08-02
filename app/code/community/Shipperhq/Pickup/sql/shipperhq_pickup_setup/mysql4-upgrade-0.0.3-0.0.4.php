<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

$pickupChosen = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 60,
    'comment' => 'ShipperHQ Pickup Selection',
    'nullable' => 'true');

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_item'), 'pickup_chosen')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'pickup_chosen', $pickupChosen);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_item'), 'pickup_chosen')){
   $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'pickup_chosen', $pickupChosen);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'pickup_chosen')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'pickup_chosen', $pickupChosen);
}

$installer->endSetup();



