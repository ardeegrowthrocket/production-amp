<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 1:41 PM
 */

class Growthrocket_Content_Model_Template_Filter extends Varien_Filter_Template{

    protected $fitment = array();

    public function setFitment($fitment){
        $this->fitment = $fitment;
    }

    public function getFitment(){
        return $this->fitment;
    }

    public function customVarDirective($construction){
        $params = $this->_getIncludeParameters($construction[2]);
        $replaceValue = '{no custom var value}';
        if (isset($params['code'])) {
            $variable = Mage::getModel('core/variable')
                ->setStoreId($this->getStoreId())
                ->loadByCode($params['code']);
            if($variable && $variable->getVariableId()){
                $plainValues = explode('|',$variable->getPlainValue());
                $chosenKey = array_rand($plainValues, 1);
                $replaceValue = $plainValues[$chosenKey];
            }

        }
        return $replaceValue;
    }

    protected function _getIncludeParameters($value)
    {
        $tokenizer = new Varien_Filter_Template_Tokenizer_Parameter();
        $tokenizer->setString($value);
        $params = $tokenizer->tokenize();
        foreach ($params as $key => $value) {
            if (substr($value, 0, 1) === '$') {
                $params[$key] = $this->_getVariable(substr($value, 1), null);
            }
        }
        return $params;
    }

    public function getStoreId()
    {
        if (null === $this->_storeId) {
            $this->_storeId = Mage::app()->getStore()->getId();
        }
        return $this->_storeId;
    }

    public function getWebsiteId(){
        if(null == $this->_websiteId){
            $this->_websiteId = Mage::app()->getStore()->getWebsiteId();
        }
        return $this->_websiteId;
    }

    /**
     * @return Mage_Core_Model_Resource_Resource
     */
    protected function getCoreResource(){
        return Mage::getResourceModel('core/resource');
    }

    /**
     * @return Magento_Db_Adapter_Pdo_Mysql
     */
    protected  function getReader(){
        return $this->getCoreResource()->getReadConnection();
    }
}