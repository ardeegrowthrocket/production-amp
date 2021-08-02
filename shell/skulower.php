<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/27/17
 * Time: 12:20 AM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract {
    public function run(){
        $_collection = Mage::getModel('catalog/product')->getCollection();

        foreach($_collection as $_product){
            $sku = $_product->getSku();
            $loweredSku = strtolower($sku);
            //$nows = str_replace(' ','-', $loweredSku);

            //Zend_Debug::dump($nows);
            $_product->setSku($loweredSku);
            $_product->save();
        }
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();