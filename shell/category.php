<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/7/17
 * Time: 10:33 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

/**
 * Run script
 *
 */
    public function run()
    {
        // TODO: Implement run() method.
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_products */
        $_products = Mage::getModel('catalog/product')->getCollection();
        $_products->addAttributeToFilter('auto_type',array('eq' => 266));
        $ctr=0;
        /** @var Mage_Catalog_Model_Product $_product */
        foreach($_products as $_product){
            echo 'Product SKU : '  . $_product->getSku();
            $ctr++;
            echo "\n";
        }

        echo 'Affected >> ' . $ctr . "\n";
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();