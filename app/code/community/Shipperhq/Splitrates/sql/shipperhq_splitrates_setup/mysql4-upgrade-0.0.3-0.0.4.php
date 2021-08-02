<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

$carriergroupShipping = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'Shipping Description',
    'nullable' => 'true',
);
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_item'), 'carriergroup_shipping')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'carriergroup_shipping', $carriergroupShipping);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_item'), 'carriergroup_shipping')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'carriergroup_shipping', $carriergroupShipping);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'carriergroup_shipping')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'carriergroup_shipping', $carriergroupShipping);
}

$installer->endSetup();