<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/4/18
 * Time: 5:31 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('grhtml/pages'));

$table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'identity' => true,
    'unsigned' => true,
    'nullable' => false,
    'primary'  => true,
),'Page id');

$table->addColumn('module', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'Route Module');

$table->addColumn('url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'Route Url');

$table->addColumn('title', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'Route Url');

$table->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    'unsigned'  => true,
    'nullable'  => false,
    'default'   => '0',
),'Store id');

$fkName = $this->getTable('grhtml/pages','store_id','core/store','store_id');
$table->addForeignKey($fkName,'store_id', $this->getTable('core/store'), 'store_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);
$conn->createTable($table);
$this->endSetup();