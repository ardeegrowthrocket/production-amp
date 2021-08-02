<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 12/03/2017
 * Time: 12:37 AM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
$conn = $this->getConnection();

$yearFk = $this->getFkName('hautopart/combination_list','year','eav/attribute_option','option_id');
$makeFk = $this->getFkName('hautopart/combination_list','make','eav/attribute_option','option_id');
$modelFk = $this->getFkName('hautopart/combination_list','model','eav/attribute_option','option_id');

$conn->addForeignKey($yearFk,$this->getTable('hautopart/combination_list'),'year',
    $this->getTable('eav/attribute_option'),'option_id');

$conn->addForeignKey($makeFk,$this->getTable('hautopart/combination_list'),'make',
    $this->getTable('eav/attribute_option'),'option_id');

$conn->addForeignKey($modelFk,$this->getTable('hautopart/combination_list'),'model',
    $this->getTable('eav/attribute_option'),'option_id');

$this->endSetup();