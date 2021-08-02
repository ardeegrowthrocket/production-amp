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

    $carriergroupDetails = array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Carrier Group Details',
        'nullable' => 'true',
    );

    $carriergroupHtml = array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Carrier Group Html',
        'nullable' => 'true',
    );

    $displayMerged = array(
        'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' => 'Checkout display type',
        'nullable' => 'false',
        'default'  => '1'
    );

    $splitRates = array(
        'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' => 'Shipperhq Split Rates',
        'nullable' => 'false',
        'default'  => '0'
    );

    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carriergroup', $carriergroupAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carriergroup_id', $carriergroupID);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carriergroup_shipping_details', $carriergroupDetails);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carriergroup_shipping_html', $carriergroupHtml);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'checkout_display_merged', $displayMerged);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'split_rates', $splitRates);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup_id', $carriergroupID);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup', $carriergroupAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup_shipping_details', $carriergroupDetails);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'carriergroup', $carriergroupAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'carriergroup', $carriergroupAttr);
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'carriergroup_id', $carriergroupID);
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'carriergroup_id', $carriergroupID);

} else  {

    $quoteAddressTable = $installer->getTable('sales/quote_address');
    $quoteRateTable = $installer->getTable('sales/quote_address_shipping_rate');
    $quoteItemTable = $installer->getTable('sales/quote_item');
    $orderItemTable = $installer->getTable('sales/order_item');

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'carriergroup_id')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD carriergroup_id text COMMENT 'Carrier Group ID';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'carriergroup')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD carriergroup text COMMENT 'Carrier Group';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'carriergroup_shipping_html')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD carriergroup_shipping_html text COMMENT 'shipperhq splitrates';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'carriergroup_shipping_details')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD carriergroup_shipping_details text COMMENT 'shipperhq splitrates';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'checkout_display_merged')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD checkout_display_merged  smallint(1) unsigned NOT NULL default '0' COMMENT 'shipperhq splitrates';
        ");

    }

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'split_rates')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD split_rates  smallint(1) unsigned NOT NULL default '0' COMMENT 'Shipperhq Split Rates';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteRateTable,  'carriergroup_id')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD carriergroup_id text COMMENT 'Carrier Group ID';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteRateTable,  'carriergroup')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD carriergroup text COMMENT 'Carrier Group';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteRateTable, 'carriergroup_shipping_details')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD carriergroup_shipping_details text COMMENT 'shipperhq splitrates';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteItemTable,  'carriergroup')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteItemTable} ADD carriergroup text COMMENT 'Carrier Group';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($orderItemTable,  'carriergroup')){
        $installer->run("
            ALTER IGNORE TABLE {$orderItemTable} ADD carriergroup text COMMENT 'Carrier Group';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteItemTable,  'carriergroup_id')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteItemTable} ADD carriergroup_id text COMMENT 'Carrier Group ID';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($orderItemTable,  'carriergroup_id')){
        $installer->run("
            ALTER IGNORE TABLE {$orderItemTable} ADD carriergroup_id text COMMENT 'Carrier Group ID';
        ");
    }
}


if (Mage::helper('wsalogger')->getNewVersion() >= 8) {
    if (Mage::helper('wsalogger')->getNewVersion() > 10) {
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment_grid'),'carriergroup',$carriergroupAttr);
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment'),'carriergroup',$carriergroupAttr);
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment'),'shipping_description',array(
            'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'	=> 255,
            'comment' 	=> 'Shipping Description',
            'nullable' 	=> 'true',));
        $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'carriergroup', $carriergroupAttr);
        $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'carriergroup_shipping_html', $carriergroupHtml);
        $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'carriergroup_shipping_details', $carriergroupDetails);

        if  (Mage::helper('wsalogger')->isEnterpriseEdition() && $installer->tableExists('enterprise_sales_shipment_grid_archive')) {
            $installer->getConnection()->addColumn($installer->getTable('enterprise_sales_shipment_grid_archive'),'carriergroup',$carriergroupAttr);
        }

    } else {
        $orderTable = $installer->getTable('sales/order');
        $shipmentTable = $installer->getTable('sales/shipment');
        $shipmentGridTable = $installer->getTable('sales/shipment_grid');

        if(!$installer->getConnection()->tableColumnExists($shipmentTable, 'shipping_description')){
            $installer->run("
                ALTER IGNORE TABLE {$shipmentTable} ADD shipping_description varchar(255) COMMENT 'shipperhq splitrates';
            ");
        }

        if(!$installer->getConnection()->tableColumnExists($shipmentTable, 'carriergroup')){
            $installer->run("
                ALTER IGNORE TABLE {$shipmentTable} ADD carriergroup text COMMENT 'Carrier Group';
            ");
        }

        if(!$installer->getConnection()->tableColumnExists($orderTable, 'carriergroup_shipping_details')){
            $installer->run("
                ALTER IGNORE TABLE {$orderTable} ADD carriergroup_shipping_details text COMMENT 'shipperhq splitrates';
            ");
        }

        if(!$installer->getConnection()->tableColumnExists($orderTable, 'carriergroup_shipping_html')){
            $installer->run("
                ALTER IGNORE TABLE {$orderTable} ADD carriergroup_shipping_html text COMMENT 'shipperhq splitrates';
            ");
        }
        if(!$installer->getConnection()->tableColumnExists($orderTable, 'carriergroup')){
            $installer->run("
                ALTER IGNORE TABLE {$orderTable} ADD carriergroup text COMMENT 'Carrier Group';
            ");
        }

        if(!$installer->getConnection()->tableColumnExists($shipmentGridTable, 'carriergroup')){
            $installer->run("
                ALTER IGNORE TABLE {$shipmentGridTable} ADD carriergroup text COMMENT 'Carrier Group';
            ");
        }
    }
}

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='order';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'carriergroup',
    	backend_type	= 'text',
    	frontend_input	= 'text';

select @attribute_set_id:=attribute_set_id from {$this->getTable('eav_attribute_set')} where entity_type_id=@entity_type_id;
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='carriergroup';
select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='General' and attribute_set_id=@attribute_set_id;

insert ignore into {$this->getTable('eav_entity_attribute')}
    set entity_type_id 		= @entity_type_id,
    	attribute_set_id 	= @attribute_set_id,
    	attribute_group_id	= @attribute_group_id,
    	attribute_id		= @attribute_id;

insert ignore into {$this->getTable('eav_attribute')}
	set entity_type_id 	= @entity_type_id,
		attribute_code 	= 'carriergroup_shipping_details',
		backend_type	= 'text',
    	frontend_input	= 'text';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='carriergroup_shipping_details';
select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='General' and attribute_set_id=@attribute_set_id;

insert ignore into {$this->getTable('eav_entity_attribute')}
    set entity_type_id 		= @entity_type_id,
    	attribute_set_id 	= @attribute_set_id,
    	attribute_group_id	= @attribute_group_id,
    	attribute_id		= @attribute_id;

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='shipment';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'carriergroup',
    	backend_type	= 'text',
    	frontend_input	= 'text';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'shipping_description',
    	backend_type	= 'varchar',
    	frontend_input	= 'text';

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='order';

insert ignore into {$this->getTable('eav_attribute')}
	set entity_type_id 	= @entity_type_id,
		attribute_code 	= 'carriergroup_shipping_html',
		backend_type	= 'text',
    	frontend_input	= 'text';
");

$installer->endSetup();