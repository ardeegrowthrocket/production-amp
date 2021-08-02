<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/7/17
 * Time: 8:20 PM
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

        /** @var Mage_Catalog_Model_Product $_product */
        foreach($_products as $_product){
            $webs = $_product->getWebsiteIds();
            if(count($webs) == 0){
                /** @var Mage_Catalog_Model_Product_Website $_productWebsites */
                $_productWebsites = Mage::getModel('catalog/product_website');
                $_productWebsites->addProducts(array(1),array($_product->getId()));
                $_productWebsites->save();
            }

        }
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();