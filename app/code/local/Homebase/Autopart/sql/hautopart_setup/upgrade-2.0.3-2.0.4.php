<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 12/03/2017
 * Time: 1:13 AM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$conn->dropColumn($this->getTable('hautopart/combination_label'),'parent');

$this->endSetup();