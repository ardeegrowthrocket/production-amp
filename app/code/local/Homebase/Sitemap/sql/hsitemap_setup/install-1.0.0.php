<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/10/17
 * Time: 8:56 PM
 */

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();

/**
 * Create table 'sitemap'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('hsitemap/multimap'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Sitemap Id')
    ->addColumn('filename', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
    ), 'Sitemap Filename')
    ->addColumn('path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Sitemap Path')
    ->addColumn('time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
    ), 'Sitemap Time')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Store id')
    ->addIndex($installer->getIdxName('hsitemap/multimap', array('store_id')),
        array('store_id'))
    ->addForeignKey($installer->getFkName('hsitemap/multimap', 'store_id', 'core/store', 'store_id'),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Google Sitemap');

$installer->getConnection()->createTable($table);

$installer->endSetup();
