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

$table = $conn->newTable($this->getTable('hautopart/combination_list'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ),'Comibnation Id')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,null,array(
        'unsigned'  => true,
    ))
    ->addColumn('year', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ))
    ->addColumn('make', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ))
    ->addColumn('model', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
    ))
    ->addColumn('position', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true
    ));

$conn->createTable($table);
$this->endSetup();