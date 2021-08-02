<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 17/04/2018
 * Time: 1:13 AM
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
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_collection */
        $_collection = Mage::getModel('catalog/product')->getCollection();
        $_collection->addFieldToFilter('attribute_set_id',9);


        $skuWithNoImages = array();
        /** @var Homebase_Autopart_Model_Product $_product */
        foreach($_collection as $_product) {
            $_rproduct = Mage::getModel('catalog/product')->load($_product->getId());
            if($_rproduct->getMediaGalleryImages()->count() == 0){
                array_push($skuWithNoImages, $_rproduct->getSku());
            }else{
                Zend_Debug::dump($_rproduct->getMediaGalleryImages()->count());
            }

        }
        $message = implode(',', $skuWithNoImages);
        Mage::log($message, null, 'skunoimages.log', true);
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();