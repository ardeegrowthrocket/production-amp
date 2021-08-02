<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 09/03/2017
 * Time: 7:37 PM
 */
/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql  $conn */
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('hautopart/customer_combination'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ),'Comibnation Id')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER,null,array(
        'unsigned'  => true,
    ))
    ->addColumn('customer_email', Varien_Db_Ddl_Table::TYPE_TEXT,null,array(
        'unsigned'  => true,
    ))
    ->addColumn('year', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ))
    ->addColumn('make', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ))
    ->addColumn('model', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true
    ))
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true
    ))
    ->addColumn('is_sync', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        'unsigned'  => true,
        'default' => 0
    ))
    ->addColumn('var', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned'  => true
    ))
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'unsigned'  => true,
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT
    ));

$conn->createTable($table);
$this->endSetup();