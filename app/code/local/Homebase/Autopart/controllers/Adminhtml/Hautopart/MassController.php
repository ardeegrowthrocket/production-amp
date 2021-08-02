<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/20/17
 * Time: 4:32 PM
 */

class Homebase_Autopart_Adminhtml_Hautopart_MassController extends Mage_Adminhtml_Controller_Action{


    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    public function uploadAction(){
        /** @var Homebase_Autopart_Helper_Uploader $_helper */
        $_helper = Mage::helper('hautopart/uploader');
        $path = $_helper->handleCsvUpload();
        $abspath = $_helper->getCsv($path);

        $file = fopen($abspath, 'r');
        if($file !== false){
            $ctr = 0;
            $year = array();
            $make = array();
            $model = array();
            while(($data = fgetcsv($file)) !== false){
                if($ctr!=0){
                    $year[] = $data[1];
                    $make[] = strtolower(trim($data[2]));
                    $model[] = strtolower(trim($data[3]));
                }
                $ctr++;
            }
            fclose($file);
            $unique_year = array_unique($year,SORT_NUMERIC);
            $unique_make = array_unique($make, SORT_STRING);
            $unique_model = array_unique($model, SORT_STRING);

            //Extract new year options

            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $year_attribute */
            $year_attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'auto_year');
            $existing_year = array();
            if($year_attribute->usesSource()){
                $options = $year_attribute->getSource()->getAllOptions();
                foreach($options as $option){
                    if(trim($option['label']) != ''){
                        $existing_year[] = $option['label'];
                    }
                }
            }
            $new_year = array_diff($unique_year,$existing_year);

            //Extract new make options

            $make_attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'auto_make');
            $existing_make = array();
            if($make_attribute->usesSource()){
                $options = $make_attribute->getSource()->getAllOptions();
                foreach($options as $option){
                    if(trim($option['label']) != ''){
                        $existing_make[] = strtolower(trim($option['label']));
                    }
                }
            }
            $new_make = array_diff($unique_make,$existing_make);


            //Extract new make options

            $model_attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'auto_model');
            $existing_model = array();
            if($model_attribute->usesSource()){
                $options = $model_attribute->getSource()->getAllOptions();
                foreach($options as $option){
                    if(trim($option['label']) != ''){
                        $existing_model[] = strtolower(trim($option['label']));
                    }
                }
            }
            $new_model = array_diff($unique_model,$existing_model);

            /** @var Mage_Eav_Model_Entity_Setup $setup */
            $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
            $setup->startSetup();

//            Zend_Debug::dump($new_make);

//            //year options
            if(!empty($new_year)){
                foreach($new_year as $value){
                    $option = array(
                        'attribute_id' => $year_attribute->getId(),
                        'value' => [[0 => $value]]
                    );
                    $setup->addAttributeOption($option);
                }
            }

            //make options
            if(!empty($new_make)){
                foreach($new_make as $value){
                    $option = array(
                        'attribute_id' => $make_attribute->getId(),
                        'value' => [[0 => $value]]
                    );
                    $setup->addAttributeOption($option);
                }
            }
            //model options
            if(!empty($new_model)){
                foreach($new_model as $value){
                    $option = array(
                        'attribute_id' => $model_attribute->getId(),
                        'value' => [[0 => $value]]
                    );
                    $setup->addAttributeOption($option);
                }
            }
            $setup->endSetup();


            //Start Associations

            $file = fopen($abspath, 'r');

            if($file !== false){
                $ctr=0;
                /** @var Homebase_Autopart_Helper_Data $helper */
                $helper = Mage::helper('hautopart');
                while(($data = fgetcsv($file)) !== false){
                    if($ctr!=0){
                        $sku = strtolower(str_replace(' ','-',$data[0]));
                        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
                        if($_product && $_product->getId()){
                            $yearOptionId = $helper->getAttributeOptionId(strtolower($data[1]),$year_attribute->getId());
                            $makeOptionId = $helper->getAttributeOptionId(strtolower($data[2]), $make_attribute->getId());
                            $modelOptionId = $helper->getAttributeOptionId(strtolower($data[3]),$model_attribute->getId());
                            /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
                            $_mixes = Mage::getModel('hautopart/mix')->getCollection();
                            $_mixes->addFieldToFilter('product_id', $_product->getId());
                            $_mixes->addFieldToFilter('year',$yearOptionId);
                            $_mixes->addFieldToFilter('make',$makeOptionId);
                            $_mixes->addFieldToFilter('model', $modelOptionId);
                            if($_mixes->count() < 1){
                                if($yearOptionId != '' && $makeOptionId !='' && $modelOptionId !='') {
                                    $_mix = Mage::getModel('hautopart/mix');
                                    $_mix->setProductId($_product->getId());
                                    $_mix->setYear($yearOptionId);
                                    $_mix->setMake($makeOptionId);
                                    $_mix->setModel($modelOptionId);
                                    $_mix->save();
                                }
                            }
                        }
                    }
                    $ctr++;
                }
            }
        }
    }
}