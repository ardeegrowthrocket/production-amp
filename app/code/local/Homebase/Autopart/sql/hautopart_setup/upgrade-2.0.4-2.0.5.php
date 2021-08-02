<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/27/17
 * Time: 11:01 PM
 */

$attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'short_description');
$attributeModel->setIsUserDefined(1);
$attributeModel->save();
