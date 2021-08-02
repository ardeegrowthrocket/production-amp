<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

$originalShippingAddress = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'SHQ Original Shipping Address',
    'nullable' => 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'orig_shipping_address')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'orig_shipping_address', $originalShippingAddress);
}

$installer->endSetup();



