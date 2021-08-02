<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();


$version = Mage::helper('wsalogger')->getNewVersion();

$pickupLocation = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 30,
    'comment' => 'ShipperHQ Pickup Location',
    'nullable' => 'true');

$pickupDate = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 20,
    'comment' => 'ShipperHQ Dispatch Date',
    'nullable' => 'true');

$timeSlot = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 20,
    'comment' => 'ShipperHQ Time Slot',
    'nullable' => 'true');

$latitude = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 20,
    'comment' => 'ShipperHQ Pickup Location Latitude',
    'nullable' => 'true');

$longitude = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 20,
    'comment' => 'ShipperHQ Pickup Location Longitude',
    'nullable' => 'true');


$installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'time_slot', $timeSlot);
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_location', $pickupLocation);
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_latitude', $latitude);
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_longitude', $longitude);

$installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_location', $pickupLocation);
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'time_slot', $timeSlot);
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_latitude', $latitude);
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_longitude', $longitude);

$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_location', $pickupLocation);
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'time_slot', $timeSlot);
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_latitude', $latitude);
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_longitude', $longitude);


$installer->endSetup();