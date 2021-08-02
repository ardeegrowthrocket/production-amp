<?php

$this->startSetup();

$conn = $this->getConnection();

$conn = $this->getConnection();

$conn->addColumn($this->getTable('hautopart/attribute_images'),'website_id',array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'unsigned' => true,
    'nullable' => false,
    'default' => 1,
    'comment' => 'Website Id'
));

$websiteFk = $this->getFkName('hautopart/attribute_images','website_id','core/website','website_id');

$conn->addForeignKey($websiteFk,$this->getTable('hautopart/attribute_images'),'website_id',
    $this->getTable('core/website'),'website_id');

$this->endSetup();