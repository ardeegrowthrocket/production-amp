<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $installer->getTable('aitoc_aitcbp_groups'),
        'is_round',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 1,
            'nullable' => false,
            'default'  => 0,
            'comment'  => 'round decimilar prices'
        )
    );

$installer->endSetup();
