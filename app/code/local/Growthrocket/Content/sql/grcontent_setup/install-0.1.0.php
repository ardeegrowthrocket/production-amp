<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 3:12 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$contentTable = $conn->newTable($this->getTable('grcontent/content'));


$contentTable->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'identity' => true,
    'unsigned' => true,
    'nullable' => false,
    'primary'  => true,
),'Page id');

$contentTable->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'Content Name');

$contentTable->addColumn('content', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'Content');

$contentTable->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    'unsigned'  => true,
    'nullable'  => false,
    'default'   => '0',
),'Store id');

$fkName = $this->getTable('grcontent/content','store_id','core/store','store_id');

$contentTable->addForeignKey($fkName,'store_id', $this->getTable('core/store'), 'store_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);

$conn->createTable($contentTable);

$this->endSetup();