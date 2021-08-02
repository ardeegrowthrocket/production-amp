<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 05/05/2017
 * Time: 2:48 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this  */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

//$table = $conn->newTable($this->getTable('hautopart/auto_combination_index'))
//    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
//        'identity'  => true,
//        'unsigned'  => true,
//        'nullable'  => false,
//        'primary'   => true,
//    ),'Unique identifier of record')
//    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT,255, array(
//        'nullable'  => false,
//    ),'Auto part route type')
//    ->addColumn('path', Varien_Db_Ddl_Table::TYPE_TEXT,255, array(
//        'nullable'  => false,
//    ),'Auto part combination route')
//    ->addColumn('combination', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
//        'nullable' => true
//    ),'Serialized fitment combination')
//    ->addColumn('products', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
//        'nullable'  => false,
//    ),'Associated products for given path')
//    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
//        'unsigned'  => true,
//        'nullable'  => false,
//        'default'   => 0
//    ),'Store Id');