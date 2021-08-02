<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/10/18
 * Time: 12:45 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this  */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$fitmentBoxTable = $this->getTable('hautopart/combination');

$storeFk = $this->getFkName('hautopart/combination','store_id','core/store','store_id');
$conn->dropForeignKey($fitmentBoxTable, $storeFk);

$this->endSetup();
