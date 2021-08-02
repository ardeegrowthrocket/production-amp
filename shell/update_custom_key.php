<?php

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    public function run()
    {
        $counter = 0;
        $attribute_code = 'custom_url_key';
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('sku','name'));

        foreach ($collection as $product) {

            $productName = $product->getName();
            $counter++;
            try{

                $_product = Mage::getModel('catalog/product')->load($product->getId());
                $resource = $product->getResource();
                $sku = $product->getSku();

                $_product->setData($attribute_code, $sku);
                $resource->saveAttribute($_product, $attribute_code);

                echo "({$counter})Update on {$productName}" . PHP_EOL;

            }catch (Exception $exception) {
                echo "Error on {$productName}" . PHP_EOL;
            }
        }
    }

}
$shell = new Mage_Shell_Compiler();
$shell->run();