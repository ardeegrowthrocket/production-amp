<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if  (Mage::helper('wsalogger')->getNewVersion() > 10 ) {
    $lifeGateAttr = array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' 	=> 'ShipperHQ Liftgate Required',
        'length'  	=> '1',
        'nullable' 	=> 'false',
        'default'   => 0);

    $notifyReqdAttr = array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' 	=> 'ShipperHQ Notify Required',
        'length'  	=> '1',
        'nullable' 	=> 'false',
        'default'   => 0);

    $insideDeliveryAttr = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment'   => 'ShipperHQ Inside Delivery',
        'length'    => '1',
        'nullable'  => 'false',
        'default'   => 0);

    $destinationTypeAttr =  array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' 	=> 'ShipperHQ Address Type',
        'nullable' 	=> 'true',
    );

    $freightQuoteIdAttr =  array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' 	=> 'ShipperHQ Freight Quote Id',
        'nullable' 	=> 'true',
    );

    $isFreightRate = array(
        'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' => 'ShipperHQ Freight Rate',
        'nullable' => 'false',
        'default'  => '0'
    );

    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'liftgate_required', $lifeGateAttr );
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'notify_required',$notifyReqdAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'inside_delivery',$insideDeliveryAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'destination_type',$destinationTypeAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'freight_quote_id',$freightQuoteIdAttr);

    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'liftgate_required',$lifeGateAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'notify_required',$notifyReqdAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'inside_delivery',$insideDeliveryAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'destination_type',$destinationTypeAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'freight_quote_id',$freightQuoteIdAttr);

    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'freight_rate', $isFreightRate);


} else {

    $installer->run("

	ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD liftgate_required tinyint(1);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD notify_required tinyint(1);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD inside_delivery tinyint(1);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD destination_type varchar(30);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')} ADD freight_quote_id varchar(30);

	ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD liftgate_required tinyint(1);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD notify_required int(1);
    ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD inside_delivery int(1);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD destination_type varchar(30);
	ALTER IGNORE TABLE {$this->getTable('sales_flat_order')} ADD freight_quote_id varchar(30);

	");
}

$installer->endSetup();
