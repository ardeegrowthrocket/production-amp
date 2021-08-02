<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

if (Mage::helper('wsalogger')->getNewVersion() > 10) {

    $splitRates = array(
        'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' => 'Shipperhq Split Rates',
        'nullable' => 'false',
        'default'  => '0'
    );
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'split_rates')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'split_rates', $splitRates);
    }

} else  {

    $quoteAddressTable = $installer->getTable('sales/quote_address');
    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'split_rates')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD split_rates  smallint(1) unsigned NOT NULL default '0' COMMENT 'Shipperhq Split Rates';
        ");
    }

}