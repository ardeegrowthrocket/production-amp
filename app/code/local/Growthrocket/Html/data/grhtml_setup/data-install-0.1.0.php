<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/5/18
 * Time: 12:01 PM
 */

/** @var Mage_Core_Model_Resource_Setup $this */
/** @var Mage_Core_Model_Resource_Resource $resource */
$resource = Mage::getResourceModel('core/resource');

$htmlTable = $this->getTable('grhtml/pages');

$this->getConnection()->insert($htmlTable,array(
    'module' => 'Homebase_Auto',
    'url' => 'make/jeep.html',
    'title' => 'Jeep Parts - OEM Jeep & Aftermarket Parts | Allmoparparts',
    'store_id' => 1
));
$this->getConnection()->insert($htmlTable,array(
    'module' => 'Homebase_Auto',
    'url' => 'make/ram.html',
    'title' => 'Ram Parts - Discount OEM Ram & Truck Parts | Allmoparparts',
    'store_id' => 1
));
