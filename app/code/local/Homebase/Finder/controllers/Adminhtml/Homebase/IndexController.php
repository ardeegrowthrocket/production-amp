<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/5/16
 * Time: 2:12 PM
 */

class Homebase_Finder_Adminhtml_Homebase_IndexController extends Mage_Adminhtml_Controller_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    public function newAction(){
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('hfinder/adminhtml_record_edit');
        $this->_addContent($block)->renderLayout();
    }
    public function saveAction(){
        if(isset($_FILES['mapping_file'])){
            $file = file_get_contents($_FILES['mapping_file']['tmp_name']);
            $lines = explode(PHP_EOL, $file);
            $records = array();
            foreach($lines as $line){
                $records[]  = str_getcsv($line);
            }
//            Zend_Debug::dump($records);
            foreach ($records as $record){
                if(count($record) == 4){
                    $model  = Mage::getModel('hfinder/record');
                    $_collection = Mage::getModel('hfinder/record')->getCollection();
                    $model->setYear($record[0]);
                    $model->setMake($record[1]);
                    $model->setModel($record[2]);
                    $model->setCategory($record[3]);
                    $model->save();
                }
            }
        }
    }
}