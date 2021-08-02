<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/17
 * Time: 10:23 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('hfitment/fitment_route'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ),'Route Id');

$conn->createTable($table);
$this->endSetup();
