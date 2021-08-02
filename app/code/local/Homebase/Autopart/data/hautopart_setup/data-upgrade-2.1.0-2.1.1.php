<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 27/07/2018
 * Time: 11:46 AM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
/** @var Mage_Core_Model_Resource_Resource $resource */
$resource = Mage::getResourceModel('core/resource');

$entityTable = $resource->getTable('catalog/product');
$combinationTable = $this->getTable('hautopart/combination_list');

/** @var Magento_Db_Adapter_Pdo_Mysql $reader */
$reader = $resource->getReadConnection();


$results  = $reader->select()
    ->from($entityTable,array('entity_id','sku'))
    ->query();

foreach($results as $result){
    $this->updateTableRow($combinationTable,'product_id',$result['entity_id'],'product_sku',$result['sku']);
}