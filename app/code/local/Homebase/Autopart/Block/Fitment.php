<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/30/17
 * Time: 3:53 PM
 */

class Homebase_Autopart_Block_Fitment extends Mage_Core_Block_Template {
    /** @var $_helper Homebase_Autopart_Helper_Data  */
    protected $_helper;

    protected $_target;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('homebase/product/fitment.phtml');
        $this->_helper = Mage::helper('hautopart');
        $this->_target = null;
        $this->setData('cache_lifetime',null);


    }
    public function getProduct(){
        $_product = Mage::registry('current_product');
        if($_product instanceof Homebase_Autopart_Model_Product){
            return $_product;
        }else{
            throw  new Exception('Product type not supported.');
        }
    }

    public function getFitment($_product){
        $_store = $this->_getStore();

        $_collection = new Varien_Data_Collection();
        $_fitment = Mage::getModel('hautopart/mix')->getCollection()
            ->addFieldToFilter('product_id', $_product->getId());
        $ndx = 0;
        $target = -1;

        $fitments = array();
        foreach($_fitment as $fitment){
            if(Mage::getStoreConfig('fitment/configuration/enable', $_store)){
                $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $_store));
                if(!in_array($fitment->getMake(),$allowedMakes)){
                    continue;
                }
            }
            $fitments[] = array(
                'combination'   => implode(',',$fitment->__toArray(array('year','make','model'))),
                'year'  => $this->getLabel($fitment->getYear()),
                'make'  => $this->getLabel($fitment->getMake()),
                'model' => $this->getLabel($fitment->getModel())
            );
        }
        uasort($fitments,array($this,'asort'));
        foreach($fitments as $fitment){
            $item = new Varien_Object();
            $item->setCombination($fitment['combination']);
            $item->setYear($fitment['year']);
            $item->setMake($fitment['make']);
            $item->setModel($fitment['model']);
            $_collection->addItem($item);
            if($item->getCombination() == $this->getMatchedCombination()){
                $target = $ndx;
            }
            $ndx++;
        }
        $_collection->setOrder('model');
        if($this->isYmmUsed()){
            if($target != -1){
                $this->_target = $_collection->getItemById($target);
                $_collection->removeItemByKey($target);
            }
        }
        $helper = Mage::helper('hautopart');
        $helper->setOrder('model');
        $_collection = $helper->sortCollection($_collection);
        $helper->setOrder('year');
        $helper->setDirection('DESC');
        $_collection = $helper->sortCollection($_collection);
        return $_collection;
    }
    public function getLabel($optionId){
        $label = $this->_helper->getOptionValue($optionId);
        if($label == '1500') {
            $label = '1500 DS';
        }
        return ucwords($label);
    }

    public function isYmmUsed(){
        $_cookie = Mage::getSingleton('core/cookie');
        return $_cookie->get('fitment') !== false;
//        $_session = Mage::getSingleton('core/session');
//        $isUsed = Mage::getSingleton('core/session')->getData('q');
//        if($isUsed){
//            $ymm = $_session->getYmm();
//            $match = $this->matchesCurrentProduct($ymm['values']);
//            if($match){
//                return true;
//            }else{
//                return false;
//            }
//        }
//        return false;
    }

    public function getYmmLabel(){
        if(!$this->isYmmUsed()){
            return array();
        }
        $fitmentArray = $this->getCurrentYmm();
        $_year = Mage::getModel('hautopart/label')->load($fitmentArray['year'],'option');
        $_make = Mage::getModel('hautopart/label')->load($fitmentArray['make'],'option');
        $_model = Mage::getModel('hautopart/label')->load($fitmentArray['model'],'option');
        $labels = array($_year->getLabel(),$_make->getLabel(),$_model->getLabel());
        if($this->matchesCurrentProduct($fitmentArray)){
            return $labels;
        }else{
            return array();
        }
//        $_session = Mage::getSingleton('core/session');
//        if($this->isYmmUsed()){
//            $ymm = $_session->getYmm();
//            //$this->matchesCurrentProduct($ymm['values']);
//            //return $ymm['labels'];
//        }
        //return 0;
    }

    public function matchesCurrentProduct($values){
        $_product = $this->getProduct();
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
        $_mixes = Mage::getModel('hautopart/mix')->getCollection()
            ->addFieldToFilter('make',$values['make'])
            ->addFieldToFilter('model',$values['model'])
            ->addFieldToFilter('year',$values['year']);
        return in_array($_product->getId(),$_mixes->getColumnValues('product_id'));
    }
    public function getCurrentYmm(){
        if(!$this->isYmmUsed()){
            return;
        }
        $_cookie = Mage::getSingleton('core/cookie');
        $path = $_cookie->get('fitment');
        $_collection = Mage::getResourceSingleton('hauto/index_combination');
        $fitment = $_collection->fetchFitment($path,'year');
        $fitmentArray = unserialize($fitment);
        return $fitmentArray;
    }
    public function getYmm(){
        if(!$this->isYmmUsed()){
            return;
        }
        $fitmentArray = $this->getCurrentYmm();
        $fitmentValues = array_values($fitmentArray);
        return $fitmentValues;
//        $_session = Mage::getSingleton('core/session');
//        if($this->isYmmUsed()){
//            $params = $_session->getYmm();
//            $params = $params['values'];
//            $ymm = array();
//            foreach($params as $key => $param){
//                if($key !== 'category'){
//                    $ymm[] = $param;
//                }
//            }
//            return $ymm;
//        }
//        return 0;
    }
    public function getMatch(){
        if(is_null($this->_target)){
            return -1;
        }
        return $this->_target;
    }
    public function getMatchedCombination(){
        if(is_null($this->getYmm())){
            return;
        }
        return implode(',', $this->getYmm());
    }
    public function asort($a,$b){
        return strcmp($b['year'],$a['year']);
    }
    protected function _getStore(){
        $storeCode = $this->getRequest()->getStoreCodeFromPath();
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store');
        $store->load($storeCode,'code');
        return $store;
    }
}