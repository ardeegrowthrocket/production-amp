<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 11/03/2017
 * Time: 3:14 PM
 */


/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$conn->changeColumn($this->getTable('hautopart/combination'),'year','year',array(
    'type'  => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned'  => true,
    'comment'   => 'Year Attribute ID',
    'length'    => 10
));

$conn->changeColumn($this->getTable('hautopart/combination'),'make','make',array(
    'type'  => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned'  => true,
    'comment'   => 'Make Attribute ID',
    'length'    => 10
));

$conn->changeColumn($this->getTable('hautopart/combination'),'model','model',array(
    'type'  => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned'  => true,
    'comment'   => 'Model Attribute ID',
    'length'    => 10
));
$conn->dropColumn($this->getTable('hautopart/combination'), 'store_id');

$yearFk = $this->getFkName('hautopart/combination','year','eav/attribute_option','option_id');
$makeFk = $this->getFkName('hautopart/combination','make','eav/attribute_option','option_id');
$modelFk = $this->getFkName('hautopart/combination','model','eav/attribute_option','option_id');

$conn->addForeignKey($yearFk,$this->getTable('hautopart/combination'),'year',
    $this->getTable('eav/attribute_option'),'option_id');

$conn->addForeignKey($makeFk,$this->getTable('hautopart/combination'),'make',
    $this->getTable('eav/attribute_option'),'option_id');

$conn->addForeignKey($modelFk,$this->getTable('hautopart/combination'),'model',
    $this->getTable('eav/attribute_option'),'option_id');

$table = $conn->newTable($this->getTable('hautopart/combination_label'))
    ->addColumn('label_id',Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ))
    ->addColumn('parent',Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'unique'    => true,
    ))

    ->addColumn('option', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'unsigned'  => true,
        'nullable'  => false
    ))
    ->addColumn('label', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
    ))
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => 0
    ), 'Store Id')
    ->addForeignKey($this->getFkName('hautopart/combination_label','parent','hautopart/combination','id'),
        'parent', $this->getTable('hautopart/combination'),'id')
    ->addForeignKey($this->getFkName('hautopart/combination_label','option','eav/attribute_option','option_id'),
        'option',$this->getTable('eav/attribute_option'),'option_id')
    ->addForeignKey($this->getFkName('hautopart/combination_label', 'store_id','core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id');
$conn->createTable($table);
$this->endSetup();
