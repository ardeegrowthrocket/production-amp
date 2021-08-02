<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/1/17
 * Time: 1:14 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
        $_mixes = Mage::getModel('hautopart/mix')->getCollection();
        $_mixes->addFieldToFilter('model',339);

        foreach($_mixes as $_fitment){
            if(!$this->isReplacementCombinationExists($_fitment)){
                Mage::log('Correcting Fitment for >> ' . $_fitment->getProductId(), null, 'datafix.log');
                echo 'New Fitment ' . $_fitment->getProductId() . "\n";
                $this->insertFitment($_fitment);
            }
            $_fitment->delete();
            Mage::log('Removing "Town" Fitment for >> ' . $_fitment->getProductId(), null, 'datafix.log');
            echo $_fitment->getProductId() . '>> Town removed';
            echo "\n";
        }
    }
    public function isReplacementCombinationExists($_fitment){
        /** @var Homebase_Autopart_Model_Resource_Mix $query */
        $query = Mage::getModel('hautopart/mix')->getResource();
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $query->getReadConnection();
        $select = $_reader->select()
            ->from($query->getMainTable())
            ->where('product_id=?', $_fitment->getProductId())
            ->where('year=?', $_fitment->getYear())
            ->where('make=?', $_fitment->getMake())
            ->where('model=?',310);
        $_result = $select->query();
        return ($_result->rowCount() > 0 ? 1 : 0);
    }
    private function insertFitment($_fitment){
        $_mix = Mage::getModel('hautopart/mix');
        $_mix->setProductId($_fitment->getProductId());
        $_mix->setYear($_fitment->getYear());
        $_mix->setMake($_fitment->getMake());
        $_mix->setModel(310);
        $_mix->save();
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();