<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 17/10/2018
 * Time: 2:41 PM
 */
/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$table = $this->getTable('grhtml/pages');

$conn->modifyColumn($table, 'title','VARCHAR(255) NULL DEFAULT NULL');
$conn->addColumn($table, 'meta_desc', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => true,
    'default' => null,
    'length'    => Varien_Db_Ddl_Table::MAX_TEXT_SIZE,
    'after' => 'title',
    'comment' => 'Meta Description'
));

$this->endSetup();