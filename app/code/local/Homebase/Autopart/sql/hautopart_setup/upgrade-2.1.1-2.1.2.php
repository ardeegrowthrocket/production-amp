<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 06/08/2018
 * Time: 11:29 AM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql  $conn */
$conn = $this->getConnection();

$fitmentIndex = $this->getIdxName('hautopart/combination',array('year','make','model','store_id'),Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE);
$table = $this->getTable('hautopart/combination');

$conn->addIndex($table, $fitmentIndex,array('year','make','model','store_id'),Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE);

$this->endSetup();