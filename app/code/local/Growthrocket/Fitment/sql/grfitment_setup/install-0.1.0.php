<?php
$this->startSetup();

$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('grfitment/category'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,array(
        'identity' => true,
        'unsigned' => true,
        'nullable'  => false,
        'primary'   => true
    ))
    ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, array(
        'nullable' => false,
    ))
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, array(
        'unsigned' => true,
        'nullable' => false
    ))
    ->addIndex($this->getIdxName('grfitment/category',array('website_id','value_id'),Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('website_id','value_id'),array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE));
$conn->createTable($table);
$this->endSetup();