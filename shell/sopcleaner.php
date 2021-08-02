<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 1/25/18
 * Time: 7:50 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){
        /** @var Mage_Catalog_Model_Resource_Product_Collection $products */
        $products = Mage::getModel('catalog/product')->getCollection();
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load(4);

        $modelNames = array('forester','legacy','baja','outback','tribeca','xv','crosstrek','impreza','sti','wrx','brz');
        echo "Start cleanup \n";
        $products->addWebsiteFilter($store->getWebsiteId());
        foreach($products as $product){
            $_product = Mage::getModel('catalog/product')->load($product->getId());
            $name = $_product->getName();
            $segments = explode(' ', $name);
            $newSegment = array();
            foreach($segments as $segment){
                if(!in_array(strtolower($segment),$modelNames)){
                    array_push($newSegment,$segment);
                }
            }
            $newName = implode(' ', $newSegment);
            $_product->setName($newName);
            $_product->save();
        }
        echo "Done";
    }
    public function sanitizeSegments($value, $terms){
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();