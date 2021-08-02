<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/02/2018
 * Time: 9:17 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{


    protected $_autoTypeCategoryIds = array();
    protected $_websiteId = 5; //LFP

    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);

         $new =  (int)$this->getArg('new');
         $current =  $this->getArg('current');
        if(!empty($new) && !empty($current)) {
            $currentCategory = explode(',', $current);
            $categoryIds = array();
            foreach ($currentCategory as $arr) {
                array_push($categoryIds,(int) $arr);
             }
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('sku', 'auto_type'))
                ->addAttributeToFilter('auto_type', array('in' => $categoryIds))
                ->addWebsiteFilter($this->_websiteId);

            foreach ($collection as $product) {
                $_product = Mage::getModel('catalog/product')->load($product->getId());
                $existingAutoType = explode(',', $_product->getAutoType());
                $newAutoType = array_merge($existingAutoType, array($new));

                $typeLog = implode(',', array_unique($newAutoType));
                Mage::log("{$_product->getSku()}-{$typeLog}",null,'update_custom_category.log');

                $_product->setAutoType(array_unique($newAutoType));
                $_product->save();
            }
        }

    }


}

$shell = new Mage_Shell_Compiler();
$shell->run();