<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$deliveryComments =  array(
    'type'    	=> $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text',
    'comment' 	=> 'ShipperHQ Delivery Comments',
    'nullable' 	=> 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'shq_delivery_comments')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'shq_delivery_comments',$deliveryComments);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'shq_delivery_comments')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'shq_delivery_comments',$deliveryComments);
}

$installer->endSetup();
