<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/30/17
 * Time: 7:58 PM
 */

class Homebase_Autopart_Block_Ymm extends Mage_Core_Block_Template {
    /** @var Homebase_Autopart_Helper_Data $_helper  */

    protected $_helper;

    protected $_websiteId = null;

    protected $_year;

    protected $_make;

    protected $_model;

    protected $_currentFitment = null;

    protected $_currentFitmentLabel;

    protected $_enableFitmentMaker;

    protected $_compatibilityHelper;

    protected $_fitmentMaker;

    public function __construct()
    {
        $this->getCurrentFitment();
        $this->_compatibilityHelper = Mage::helper('compatibilitychecker');
        $this->_helper = Mage::helper('hautopart');
        $this->setTemplate('homebase/ymm/box.phtml');
    }

    protected function _prepareLayout()
    {

        $this->_enableFitmentMaker = Mage::getStoreConfig('fitment/configuration/enable');
        $this->_fitmentMaker  = Mage::getStoreConfig('fitment/configuration/make');
        $this->_websiteId = Mage::app()->getStore()->getWebsiteId();
        $this->_year = $this->getYear();
        $this->_make = $this->getMake();
        $this->_model = $this->getModel();

        return $this;
    }

    /**
     * @return object
     */
    public function getYear()
    {

        if(!$this->_year){
            $collection = Mage::getModel('hautopart/combination')->getCollection();
            $collection->getSelect()->join(array('y' => 'auto_combination_list_labels'),' year=y.option',
                array('ylabel' => 'label'))
                ->group('label')
                ->where('main_table.store_id= ?', $this->_websiteId);

            if($this->_enableFitmentMaker && !empty($this->_fitmentMaker)){
                $collection->addFieldToFilter('make',array('in' => explode(',',$this->_fitmentMaker)));
            }
            
            $collection->addOrder('ylabel','Desc');

            $this->_year = $collection;
        }

        return $this->_year;
    }


    public function isUsed()
    {
        return Mage::getSingleton('core/session')->getData('q');
    }

    public function isActive()
    {
        $_session = Mage::getSingleton('core/session');
        return (int) $this->isUsed() || is_array($_session->getYmm());
    }

    public function getResult($key = '')
    {
        
        $_request = $this->getRequest();
        $_session = Mage::getSingleton('core/session');
        if ($this->isUsed()) {
            /** @var Mage_Core_Model_Cookie $_cookie */
            $_cookie = Mage::getSingleton('core/cookie');
            
            $parts = unserialize($_cookie->get('qymm'));
            if(array_key_exists('year',$parts) && array_key_exists('make',$parts) && array_key_exists('model',$parts)){
                $year = $this->_helper->getOptionValue($parts['year']);
                $make = $this->_helper->getOptionValue($parts['make']);
                $model = $this->_helper->getOptionValue($parts['model']);
                $ymm = array(
                    'labels'  => array($year, $make, $model),
                    'values'  => $parts
                );
                $_session->setYmm($ymm);
            }
        }
        $result = $_session->getYmm();
        if($key == 'labels'){
            $result = $result['labels'];
        }else if( $key == 'values'){
            $result = $result['values'];
        }
        return $result;
    }


    public function getResultArray()
    {
        $_request = $this->getRequest();
        $_session = Mage::getSingleton('core/session');
        if ($this->isUsed()) {
            $parts = unserialize($_request->getParam('ymm_params'));
            if(array_key_exists('year',$parts) && array_key_exists('make',$parts) && array_key_exists('model',$parts)){
                $_session->setYmm($parts);
            }
        }
    }

    public function getResetUrl()
    {
        return $this->getUrl('hajax/index/reset');
    }


    public function getSelectedYear()
    {
        $fitmentArray = $this->getCurrentFitment();
        return $fitmentArray['year'];
    }

    public function getSelectedMake()
    {
        $fitmentArray = $this->getCurrentFitment();
        return $fitmentArray['make'];
    }

    public function getSelectedModel()
    {
        $fitmentArray = $this->getCurrentFitment();
        return $fitmentArray['model'];
    }


    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getMake()
    {
        if(!$this->_make){
            $collection = Mage::getModel('hautopart/combination')->getCollection();
            $collection->getSelect()->join(array('make' => 'auto_combination_list_labels'),' make=make.option',
                array('llabel' => 'label'));
            $collection->addFieldToFilter('year', $this->getSelectedYear());
            $collection->addFieldToFilter('main_table.store_id', $this->_websiteId);

            if($this->_enableFitmentMaker && !empty($this->_fitmentMaker)){
                $collection->addFieldToFilter('make',array('in' => explode(',',$this->_fitmentMaker)));
            }

            $collection->getSelect()->group('label');
            $response = array();
            foreach($collection as $item) {
                $response[] = array(
                    'id' => $item->getData('make'),
                    'label' => $item->getLlabel()
                );
            }

            $this->_make = $response;
        }

        return $this->_make;
    }

    /**
     * @return array
     */
    public function getModel()
    {
        if(!$this->_model){
            $collection = Mage::getModel('hautopart/combination')->getCollection();
            $collection->getSelect()->join(array('model' => 'auto_combination_list_labels'),' model=model.option',
                array('llabel' => 'label', 'name' => 'name'));
            $collection->addFieldToFilter('year', $this->getSelectedYear());
            $collection->addFieldToFilter('make', $this->getSelectedMake());

            if($this->_enableFitmentMaker && !empty($this->_fitmentMaker)){
                $collection->addFieldToFilter('make',array('in' => explode(',',$this->_fitmentMaker)));
            }

            $collection->getSelect()->group('label');
            $response = array();
            foreach($collection as $item) {
                $response[] = array(
                    'id' => $item->getData('model'),
                    'label' => $item->getLlabel(),
                    'name' => $item->getName()
                );
            }
            $this->_model = $response;
        }

        return $this->_model;
    }

    public function hasActiveFitmentQuery()
    {
        /** @var Mage_Core_Model_Cookie $_cookie */
        $_cookie = Mage::getSingleton('core/cookie');
        return $_cookie->get('fitment') !== false;
    }

    public function getCurrentFitment()
    {
        if(!$this->_currentFitment){
            /** @var Homebase_Auto_Model_Resource_Index_Combination $_collection */
            $_collection = Mage::getResourceSingleton('hauto/index_combination');
            /** @var Homebase_Fitment_Helper_Url $_fitmentHelper */
            $_fitmentHelper = Mage::helper('hfitment/url');
            $storeCode = Mage::app()->getStore()->getStoreId();
            /** @var Mage_Core_Model_Cookie $_cookie */
            $_cookie = Mage::getSingleton('core/cookie');
            $path = $_cookie->get('fitment');

            if(!empty($path)){
                if($_fitmentHelper->getCombinationSerialFromRoutePath($path, 'year', $storeCode) !== -1){
                    $fitment = unserialize($_fitmentHelper->getCombinationSerialFromRoutePath($path, 'year', $storeCode));
                    $this->_currentFitment =  $fitment;
                }
            }

            if(!Mage::registry('ymm_fitment')){
                Mage::register('ymm_fitment', $this->_currentFitment,true);
            }
        }

        return $this->_currentFitment;

    }

    /**
     * @return string
     */
    public function getCurrent()
    {
        $fitmentArray = $this->getCurrentFitment();
        if(!$this->_currentFitmentLabel && !empty($fitmentArray)){
            $helper =  Mage::helper('compatibilitychecker');
            $yearLabel = $helper->getLabelById($fitmentArray['year']);
            $makeLabel = $helper->getLabelById($fitmentArray['make']);
            $modelLabel = $helper->getLabelById($fitmentArray['model']);

            $this->_currentFitmentLabel = $yearLabel . ' ' . $makeLabel . ' ' . $modelLabel;
        }

        return $this->_currentFitmentLabel;
    }
}