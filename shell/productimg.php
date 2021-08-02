<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/11/17
 * Time: 7:44 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract
{
    public function run()
    {
        $_products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(
                array(
                    array(
                        'attribute' => 'image',
                        'null' => '1'
                    ),
                    array(
                        'attribute' => 'small_image',
                        'null' => '1'
                    ),
                    array(
                        'attribute' => 'thumbnail',
                        'null' => '1'
                    ),
                    array(
                        'attribute' => 'image',
                        'nlike' => '%/%/%'
                    ),
                    array(
                        'attribute' => 'small_image',
                        'nlike' => '%/%/%'
                    ),
                    array(
                        'attribute' => 'thumbnail',
                        'nlike' => '%/%/%'
                    )
                ),
                null,
                'left'
            );
        foreach ($_products as $_product) {
            echo $_product->getSku() . "\n";
        }

    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();