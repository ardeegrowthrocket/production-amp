<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

$pickupEmail = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 60,
    'comment' => 'ShipperHQ Pickup Email Address',
    'nullable' => 'true');

$pickupContactName = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 60,
    'comment' => 'ShipperHQ Pickup Contact Name',
    'nullable' => 'true');

$pickupEmailOption = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length' => 60,
    'comment' => 'ShipperHQ Pickup Email Option',
    'nullable' => 'true');

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_email')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_email', $pickupEmail);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_contact')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_contact', $pickupContactName);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_email_option')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_email_option', $pickupEmailOption);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_email')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_email', $pickupEmail);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_contact')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_contact', $pickupContactName);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_email_option')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'pickup_email_option', $pickupEmailOption);
}

$installer->endSetup();



