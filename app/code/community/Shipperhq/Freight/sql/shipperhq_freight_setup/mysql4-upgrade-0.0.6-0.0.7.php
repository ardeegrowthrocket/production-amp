<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'liftgate_required')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/quote_address'),
        'liftgate_required',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'default' => null,
            'nullable' => 'true'
        )
    );
}

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'notify_required')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/quote_address'),
        'notify_required',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'default' => null,
            'nullable' => 'true'
        )
    );
}

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'inside_delivery')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/quote_address'),
        'inside_delivery',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'default' => null,
            'nullable' => 'true'
        )
    );
}

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'liftgate_required')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/order'),
        'liftgate_required',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'default' => null,
            'nullable' => 'true'
        )
    );
}

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'notify_required')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/order'),
        'notify_required',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'default' => null,
            'nullable' => 'true'
        )
    );
}

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'inside_delivery')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/order'),
        'inside_delivery',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'default' => null,
            'nullable' => 'true'
        )
    );
}

$installer->endSetup();
