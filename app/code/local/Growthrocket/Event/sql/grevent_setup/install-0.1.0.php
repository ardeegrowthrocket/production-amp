<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/12/18
 * Time: 3:38 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this  */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();


$table = $conn->newTable($this->getTable('grevent/event'));

$table->addColumn('event_id',Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'identity' => true,
    'unsigned' => true,
    'nullable'  => false,
    'primary'   => true
))->addColumn('event_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    'nullable' => false,
    'unique' => true,
))->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT
))->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    'nullable' => true,
))->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    'nullable'  => false,
    'default' => 1,
));

$conn->createTable($table);
$this->endSetup();