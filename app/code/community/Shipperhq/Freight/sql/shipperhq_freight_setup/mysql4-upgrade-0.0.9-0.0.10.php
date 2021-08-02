<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$limitedDeliveryAttr = array(
    'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment'   => 'ShipperHQ Limited Access Delivery',
    'length'    => '1',
    'nullable'  => 'true'
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'limited_delivery')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'limited_delivery',$limitedDeliveryAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'limited_delivery')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'limited_delivery',$limitedDeliveryAttr);
}

$installer->endSetup();
