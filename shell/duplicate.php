<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/6/17
 * Time: 11:57 PM
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
        $duplicateCount = 0;
        foreach($_products as $_product){
            $_uniques = array();
            /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
            $_mixes = Mage::getModel('hautopart/mix')->getCollection()
                ->addFieldToFilter('product_id',$_product->getId());
            /** @var Homebase_Autopart_Model_Mix $_mix */
            foreach($_mixes as $_mix){
                $combinationSerial = implode('-',$_mix->toArray(array('year','make','model')));
                if(!in_array($combinationSerial,$_uniques)){
                   $_uniques[]  = $combinationSerial;
                }else{
                    $duplicateCount++;
                    Mage::log($_product->getId() . '>> ' . $combinationSerial,null, 'duplicate.log');
                    $_mix->delete();
                }
            }
        }
        Mage::log("Duplicate found : " . $duplicateCount, null, 'duplicate.log');
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();