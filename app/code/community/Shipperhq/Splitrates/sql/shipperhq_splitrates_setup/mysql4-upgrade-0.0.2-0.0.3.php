<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

if (Mage::helper('wsalogger')->getNewVersion() > 10) {

    $carriergroupAttr = array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Carrier Group',
        'nullable' => 'true',
    );

    $carriergroupID  = array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Carrier Group ID',
        'nullable' => 'true',
    );
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'carriergroup')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'carriergroup', $carriergroupAttr);
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'carriergroup_id')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'carriergroup_id', $carriergroupID);
    }

} else  {

    $quoteAddressItemTable = $installer->getTable('sales/quote_address_item');
    if(!$installer->getConnection()->tableColumnExists($quoteAddressItemTable,  'carriergroup')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressItemTable} ADD carriergroup text COMMENT 'Carrier Group';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($quoteAddressItemTable,  'carriergroup_id')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressItemTable} ADD carriergroup_id text COMMENT 'Carrier Group ID';
        ");
    }

}