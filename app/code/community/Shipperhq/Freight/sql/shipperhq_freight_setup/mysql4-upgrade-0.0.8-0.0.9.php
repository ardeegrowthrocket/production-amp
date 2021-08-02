<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$text = Mage::helper('wsalogger')->getNewVersion() > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text';

$customerAccountCarrier =  array(
    'type'    	=> $text,
    'comment' 	=> 'ShipperHQ Customer Account Carrier',
    'nullable' 	=> 'true',
);

$customerCarrierPhone =  array(
    'type'    	=> $text,
    'comment' 	=> 'ShipperHQ Customer Account Carrier Telephone',
    'nullable' 	=> 'true',
);

$customerAccountNumber =  array(
    'type'    	=> $text,
    'comment' 	=> 'ShipperHQ Customer Account Number',
    'nullable' 	=> 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'customer_carrier')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'customer_carrier', $customerAccountCarrier);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'customer_carrier')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'customer_carrier',$customerAccountCarrier);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'customer_carrier_ph')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'customer_carrier_ph', $customerCarrierPhone);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'customer_carrier_ph')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'customer_carrier_ph',$customerCarrierPhone);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'customer_carrier_account')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'customer_carrier_account', $customerAccountNumber);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'customer_carrier_account')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'customer_carrier_account',$customerAccountNumber);
}

$installer->endSetup();
