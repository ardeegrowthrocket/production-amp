<?php

$this->startSetup();
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('hautopart/combination'))
    ->addColumn('id',Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true)
        ,'Combination ID')
    ->addColumn('year', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Year Attribute')
    ->addColumn('make', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Make Attribute')
    ->addColumn('model', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Model Attribute')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Store id')
    ->addIndex($this->getIdxName('hautopart/combination',array('store_id')),
        array('store_id'))
    ->addForeignKey($this->getFkName('hautopart/combination', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE);
$conn->createTable($table);
$this->endSetup();