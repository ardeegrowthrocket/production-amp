<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$pickupPreselect = array(
    'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' => 'Shipperhq Shipper',
    'nullable' => 'false',
    'default'  => '0'
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'pickup_preselected')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'pickup_preselected', $pickupPreselect);
}

$installer->endSetup();



