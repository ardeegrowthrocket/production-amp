<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/4/17
 * Time: 10:35 AM
 */

class Homebase_Autopart_Helper_Parser  extends Mage_Core_Helper_Abstract{
    /** @var  Homebase_Autopart_Model_Resource_Label_Collection $_labelCollection*/
    protected $_labelCollection;

    /** @var Homebase_Autopart_Helper_Data $_helper */
    protected $_helper;

    public function __construct()
    {
        $this->_labelCollection = Mage::getModel('hautopart/label')->getCollection();
        $this->_helper = Mage::helper('hautopart');
    }

    public function extractMake($params, $controller){
        $fitmentUrlHelper = Mage::helper('hfitment/url');
        $path = implode('-',$params);
        $storeCode = $controller->getRequest()->getStoreCodeFromPath();
        $_store = Mage::getModel('core/store')->load($storeCode,'code');
        $storeId = $_store->getStoreId();
        return $fitmentUrlHelper->getCombinationSerialFromRoutePath($path,'make',$storeId);
    }

    public function extractMakeModel($params, $controller){
        $fitmentUrlHelper = Mage::helper('hfitment/url');
        $path = implode('-',$params);
        $storeCode = $controller->getRequest()->getStoreCodeFromPath();
        $_store = Mage::getModel('core/store')->load($storeCode,'code');
        $storeId = $_store->getStoreId();
        return $fitmentUrlHelper->getCombinationSerialFromRoutePath($path,'model',$storeId);
    }

    public function extractMakeModelYear($params,$controller){
        $fitmentUrlHelper = Mage::helper('hfitment/url');
        $path = implode('-',$params);
        $storeCode = $controller->getRequest()->getStoreCodeFromPath();
        $_store = Mage::getModel('core/store')->load($storeCode,'code');
        $storeId = $_store->getStoreId();
        return $fitmentUrlHelper->getCombinationSerialFromRoutePath($path,'year',$storeId);
    }

    public function extractMakeModelYearCategory($params){
        $year = $params[0];
        $string = array_splice($params, 1);
        $make = array_shift($string);
        $modelList = Mage::getModel('hautopart/option_model')->toOptionArray();
        array_shift($modelList);
        //extract model
        $model = '';
        $endTarget = -1;
        foreach($string as $ndx => $part){
            $model = $model . ' ' . $part;
            if($this->optionInList($modelList,$model)){

                $endTarget = $ndx;
                break;
            }
        }
        $model = trim($model);
        $category = implode(' ',array_splice($string, $endTarget + 1));
        $result = array(
            'make'  => $this->getOptionCode($make),
            'model' => $this->getOptionCode($model),
            'year'  => $this->getOptionCode($year),
            'category' => $this->_helper->getAttributeOptionId($category)
        );
        return $result;
    }

    public function getMMYCValues($params,$controller){
        $fitmentUrlHelper = Mage::helper('hfitment/url');
        $path = implode('-',$params);
        $storeCode = $controller->getRequest()->getStoreCodeFromPath();
        $_store = Mage::getModel('core/store')->load($storeCode,'code');
        $storeId = $_store->getStoreId();
        $fitmentData = $fitmentUrlHelper->getCombinationSerialFromRoutePath($path,'cat',$storeId);

        if(empty($fitmentData)) {
            $pathArr = explode('-', $path);

            $categoryId = 0;
            $labelData = $this->_helper->getCategoryIdByLabel();

            if(isset($labelData[$pathArr[0]])) {
                $year = $labelData[$pathArr[0]];
            }else {
                return false;
            }

            if(isset($labelData[$pathArr[1]])) {
                $make = $labelData[$pathArr[1]];
            }else {
                return false;
            }

            /** @var  $modelParam */
            $modelParam = implode(' ', array($pathArr[2],$pathArr[3]));
            if(isset($labelData[$modelParam])) {
                $model = $labelData[$modelParam];
                $cCounter = 4;
            }else {
                $model = $labelData[$pathArr[2]];
                $cCounter = 3;
            }

            /** @var  $category */
            $category =  join(' ', array_slice($pathArr, $cCounter, count($pathArr)));
            $category2 = str_replace("and", "&", $category);
            if(isset($labelData[$category])) {
                $categoryId = $labelData[$category];
            }elseif(isset($labelData[$category2])) {
                $categoryId = $labelData[$category2];
            }

            if(!Mage::helper('hautopart')->isCategoryUniversal($categoryId)) {
                return;
            }

            $universalFitmentData = array(
                "year" => $year,
                "make" => $make,
                "model" => $model,
                "category" => $categoryId
            );
            $fitmentData = serialize($universalFitmentData);
        }

        return $fitmentData;
    }

    public function getYmms($path, $sku, $controller){
        $fitmentUrlHelper = Mage::helper('hfitment/url');
        $storeCode = $controller->getRequest()->getStoreCodeFromPath();
        $_store = Mage::getModel('core/store')->load($storeCode,'code');
        $storeId = $_store->getStoreId();
        $websiteId = $_store->getWebsiteId();
        $response = $fitmentUrlHelper->getCombinationSerialFromRoutePath($path,'year',$storeId);
        if(empty($response)){
            return false;
        }

        $_product = Mage::getModel('catalog/product')->loadByAttribute('custom_url_key',$sku);

        /** Fallback */
        if(empty($_product)) {
            //$_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
        }

        if(!$_product){
            return false;
        }
        if(!$fitmentUrlHelper->validateSku($_product->getId(),$websiteId)){
            return false;
        }
        $fitmentParams = unserialize($response);
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $mix */
        $mix = Mage::getModel('hautopart/mix')->getCollection();
        foreach($fitmentParams as $label => $value){
            $mix->addFieldToFilter($label, $value);
        }
        $mix->addFieldToFilter('product_id',$_product->getId());
        return $mix->count() > 0;
    }

    public function optionInList($optionArray,$needle){
        foreach($optionArray as $model){
            if(strtolower(trim($model['label'])) == trim(strtolower($needle))){
                return true;
            }
        }
        return false;
    }

    public function labelExists($label){
        /** @var Homebase_Autopart_Model_Resource_Label_Collection $labelCollection */
        $labelCollection = Mage::getModel('hautopart/label')->getCollection();
        $labelCollection->addExpressionFieldToSelect('llabel','LOWER(label)','label');
        $labelCollection->getSelect()
            ->having('llabel = ? ', strtolower($label));
        return (($this->_labelCollection->count() > 0) ? 1 : 0);
    }

    public function getOptionCode($label){
        /** @var Homebase_Autopart_Model_Resource_Label_Collection $labelCollection */
        $labelCollection = Mage::getModel('hautopart/label')->getCollection();
        $labelCollection->addExpressionFieldToSelect('llabel','LOWER(label)','label');
        $labelCollection->getSelect()
            ->having('llabel = ? ', strtolower($label));
        if($labelCollection->count() == 1){
            return $labelCollection->fetchItem()->getData('option');
        }
    }

    /**
     * @param $code
     * @param string $column
     * @return string
     * @throws Zend_Cache_Exception
     */
    public function getLabel($code,$column = 'label')
    {
        $cacheId = 'auto_vehicle_label_data';
        $autoLabelArray = array();
        $result = "";

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $autoLabelArray = unserialize($data_to_be_cached);

        } else {

            $labelCollection = Mage::getModel('hautopart/label')->getCollection();
            $labelCollection->addFieldToSelect('*');

            foreach ($labelCollection as $data){
                $autoLabelArray[$data->getOption()] = array(
                    'label' => $data->getLabel(),
                    'name'  => $data->getName()
                );
            }

            Mage::app()->getCache()->save(serialize($autoLabelArray), $cacheId);
        }

        if(isset($autoLabelArray[$code])){
            $result = $autoLabelArray[$code][$column];
        }

        return $result;
    }

    public function getLink($label, $type){
        $baseUrl = Mage::getBaseUrl();
        $ymmParam = unserialize($this->_getRequest()->getParam('ymm_params'));


        $_pathHelper = Mage::helper('hauto/path');
        if($type == 'make'){
            return $baseUrl . 'make/' . strtolower($this->_helper->scrubLabel($label)) . '.html'; 
        }else if($type == 'year'){
            if(array_key_exists('make', $ymmParam) && array_key_exists('model', $ymmParam)){
                $make = $this->getLabel($ymmParam['make']);
                $make = $this->_helper->scrubLabel($make);
                $model = $this->_helper->scrubLabel($this->getLabel($ymmParam['model']));
                return $baseUrl . 'year/' . strtolower($label) . '-' . strtolower($make)  . '-' . strtolower($model) . '.html';
            }
        }else if($type == 'model'){
            if(!array_key_exists('make',$ymmParam)){
                return;
            }
            $make = $this->getLabel($ymmParam['make']);
            $make = $this->_helper->scrubLabel($make);
            return $baseUrl . 'model/' . strtolower($make) . '-' .$this->_helper->scrubLabel(strtolower($label)) . '.html';
        }else if($type == 'category'){
            if(array_key_exists('make', $ymmParam) && array_key_exists('model', $ymmParam) && array_key_exists('year', $ymmParam)){



                $year = $this->getLabel($ymmParam['year']);
                $make = $this->getLabel($ymmParam['make']);
                $make = $this->_helper->scrubLabel($make);
                $model = $this->_helper->scrubLabel($this->getLabel($ymmParam['model']));
                $category = strtolower($this->_helper->scrubLabel(trim($label)));
                return $baseUrl . 'cat/' . $year . '-' . strtolower($make) . '-' . strtolower($model) . '-' . $_pathHelper->replaceCommaWithDash($category) . '.html';
            }
        }
    }

    private function getYearIndex($params){
        $ctr = 0;
        foreach($params as $param){
            if(preg_match('/^[1-9]\d{3,}$/',$param)){
                if($param > 1800 && $param < 2099){
                    return $ctr;
                }
            }
            $ctr++;
        }
    }
}