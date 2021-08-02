<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

$pickupLocationID = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 30,
    'comment' => 'ShipperHQ Pickup Location ID',
    'nullable' => 'true');
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_location_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_location_id', $pickupLocationID);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'pickup_location_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_location_id', $pickupLocationID);
}

$installer->endSetup();



