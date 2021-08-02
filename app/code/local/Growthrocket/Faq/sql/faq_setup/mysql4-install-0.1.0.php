<?php
$installer = $this;
$installer->startSetup();
$table = $installer->getConnection()
    ->newTable('gr_faq')
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('question', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
    ), 'Question')
    ->addColumn('answer', Varien_Db_Ddl_Table::TYPE_LONGVARCHAR, null, array(
        'nullable'  => false,
    ), 'Answer')
    ->addColumn('page_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
    ), 'Page Type')
    ->addColumn('parent', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'position')
    ->addColumn('store_ids', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
    ), 'Store Ids')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'status')
    ->addColumn('position', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'position');

$installer->getConnection()->createTable($table);
$installer->endSetup();