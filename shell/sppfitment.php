<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/04/2018
 * Time: 7:54 PM
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

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $table = $resource->getTableName('hautopart/combination_list');

        $reader = $resource->getConnection('core_read');

        $_collection = Mage::getModel('catalog/product')->getCollection();
        $_collection->addFieldToFilter('attribute_set_id',9);
        $skuswithNoFitment = array();
        foreach($_collection as $_product) {
            $select = $reader->select()
                ->from($table)
                ->where('product_id=?', $_product->getId());

            $result = $reader->query($select);

            if (count($result->fetchAll()) == 0) {
                array_push($skuswithNoFitment, $_product->getSku());
            }

        }
        $message = implode(',',$skuswithNoFitment);
        Mage::log($message, null, 'skus_withno_fitment.log',true);
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();