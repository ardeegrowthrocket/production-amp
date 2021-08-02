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

$pickupPreselect = array(
    'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' => 'Shipperhq Shipper',
    'nullable' => 'false',
    'default'  => '0'
);

$pickupChosen = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 60,
    'comment' => 'ShipperHQ Pickup Selection',
    'nullable' => 'true');

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'time_slot')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'time_slot', $timeSlot);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_location')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_location', $pickupLocation);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_latitude')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_latitude', $latitude);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_longitude')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_longitude', $longitude);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_preselected')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_preselected', $pickupPreselect);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_location')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_location', $pickupLocation);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'time_slot')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'time_slot', $timeSlot);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_latitude')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_latitude', $latitude);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_longitude')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_longitude', $longitude);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'pickup_location')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_location', $pickupLocation);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'time_slot')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'time_slot', $timeSlot);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'pickup_latitude')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_latitude', $latitude);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'pickup_longitude')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'pickup_longitude', $longitude);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_item'), 'pickup_chosen')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'pickup_chosen', $pickupChosen);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'pickup_chosen')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'pickup_chosen', $pickupChosen);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_item'), 'pickup_chosen')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'pickup_chosen', $pickupChosen);
}

$installer->endSetup();