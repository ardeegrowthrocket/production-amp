<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/21/16
 * Time: 10:29 AM
 */
require_once 'abstract.php';
class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){
        $_collection = Mage::getModel("catalog/category")->getCollection()
                ->addAttributeToSelect('page_layout');
        foreach($_collection as $_category){
            $_rcategory = Mage::getModel('catalog/category')->load($_category->getId());
            if($_category->getPageLayout() =='two_columns_left'){
                $_rcategory->setPageLayout('one_column');
                $_rcategory->save();
            }
            //Zend_Debug::dump($_category->getPageLayout());
            //echo '-------------------' . "\n";
        }
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();