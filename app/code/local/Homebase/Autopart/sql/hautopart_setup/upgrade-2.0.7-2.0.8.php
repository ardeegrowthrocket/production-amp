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

$conn->addColumn($this->getTable('hautopart/combination'),'store_id',array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'unsigned'  => true,
    'nullable'  => false,
    'default'   => '0',
    'comment' => 'Store Id'
));

$storeFk = $this->getFkName('hautopart/combination','store_id','core/store','store_id');
$conn->addForeignKey($storeFk,$this->getTable('hautopart/combination'),'store_id',
    $this->getTable('core/store'),'store_id');

$this->endSetup();