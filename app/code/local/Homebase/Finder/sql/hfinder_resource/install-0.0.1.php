<?php

$this->startSetup();
$conn = $this->getConnection();

$table = $conn->newTable($this->getTable('hfinder/hfinder_base'))
            ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true,
            ),'Unique Id')
            ->addColumn('year', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
                'nullable'  => false,
            ))
            ->addColumn('make', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
                'nullable'  => false,
            ))
            ->addColumn('model', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
                'nullable'  => false,
            ))
            ->addColumn('category', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
                'nullable'  => false,
            ));
$conn->createTable($table);
$this->endSetup();