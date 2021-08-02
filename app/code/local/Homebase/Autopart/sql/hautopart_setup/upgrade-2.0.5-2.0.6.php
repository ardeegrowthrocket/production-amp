<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/11/17
 * Time: 2:48 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql  $conn */
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('hautopart/attribute_images'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ),'Image Id')
    ->addColumn('option_id',Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'nullable'  => false,
        'unsigned'  => true
    ))
    ->addColumn('img_path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
    ))
    ->addForeignKey($this->getFkName('hautopart/attribute_images', 'option_id', 'eav/attribute_option', 'option_id'),
        'option_id', $this->getTable('eav/attribute_option'), 'option_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE);
$conn->createTable($table);
$this->endSetup();