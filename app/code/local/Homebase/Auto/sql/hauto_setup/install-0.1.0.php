<?php

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('hauto/combination_indexer'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true
        ),'Combination Id')
        ->addColumn('route',Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
        ),'Combintation parent route')
        ->addColumn('path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
        ),'Combination path')
        ->addColumn('combination',Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
        ),'Serialized Auto combination')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ), 'Store id')
        ->addIndex($this->getIdxName('hauto/combination_indexer',array('route','path')),array('route','path'),array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
        ->addIndex($this->getIdxName('hauto/combination_indexer',array('path')),array('path'),array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT))
        ->addForeignKey($this->getFkName('hauto/combination_indexer', 'store_id', 'core/store', 'store_id'),
            'store_id', $this->getTable('core/store'), 'store_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE,
            Varien_Db_Ddl_Table::ACTION_CASCADE);
$conn->createTable($table);
$this->endSetup();