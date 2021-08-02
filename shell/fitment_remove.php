<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/7/17
 * Time: 11:15 PM
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
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
        $_mixes = Mage::getModel('hautopart/mix')->getCollection();
        $ctr= 0;
        foreach($_mixes as $_mix){
            $combination = $_mix->toArray(array('year','make','model'));
            $cleaned = array_filter($combination);
            if(count($cleaned) == 0){
                Mage::log('Affected >> ' . $_mix->getProductId(),null,'blank.log');
                $ctr++;
                $_mix->delete();
            }
        }
        Mage::log('Total Affected :: ' . $_mix->getProductId(),null,'blank.log');
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();