<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/02/2018
 * Time: 9:17 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{


    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);

        $counter = 0;
        $attribute_code = 'amp_part_number';
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect(array('sku','name'))
            ->addAttributeToFilter(
                array(
                    array('attribute'=> $attribute_code,'null' => true),
                    array('attribute'=> $attribute_code,'eq' => ''),
                    array('attribute'=> $attribute_code,'eq' => 'NO FIELD')
                ),
                '',
                'left');;

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