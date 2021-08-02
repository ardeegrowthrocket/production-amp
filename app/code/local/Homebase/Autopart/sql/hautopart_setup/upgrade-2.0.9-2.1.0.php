<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 27/07/2018
 * Time: 11:22 AM
 */

/** @var Mage_Core_Model_Resource_Setup $this  */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$conn->addColumn($this->getTable('hautopart/combination_list'),'product_sku', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment' => 'Register product SKU'
));

$this->endSetup();