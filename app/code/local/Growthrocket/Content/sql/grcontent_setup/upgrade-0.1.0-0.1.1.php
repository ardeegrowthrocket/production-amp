<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 12:39 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();


/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$pageTable = $conn->newTable($this->getTable('grcontent/page'));


$pageTable->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'identity' => true,
    'unsigned' => true,
    'nullable' => false,
    'primary'  => true,
),'Page id');

$pageTable->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'Type of page');

$pageTable->addColumn('url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
),'URL Request String');

$pageTable->addColumn('content_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'unsigned'  => true,
    'nullable'  => false,
),'Store id');

$pageTable->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    'unsigned'  => true,
    'nullable'  => false,
    'default'   => '0',
),'Store id');

$storeFkName = $this->getTable('grcontent/page','store_id','core/store','store_id');

$pageTable->addForeignKey($storeFkName,'store_id', $this->getTable('core/store'), 'store_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);

$contentFkName = $this->getTable('grcontent/page','content_id','grcontent/content','id');

$pageTable->addForeignKey($contentFkName, 'content_id', $this->getTable('grcontent/content'),'id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);


$conn->createTable($pageTable);


$this->endSetup();
