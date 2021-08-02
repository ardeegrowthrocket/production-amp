<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/2/17
 * Time: 11:50 PM
 */

class Homebase_Autopart_Helper_Path_Validator extends Mage_Core_Helper_Abstract{
    /** @var  Homebase_Autopart_Model_Resource_Label_Collection  $labelCollection*/
    protected $labelCollection;

    protected $_websiteId;

    public function __construct()
    {
        $this->_websiteId = Mage::app()->getStore()->getWebsiteId();
    }

    public function isValid($action){

        $this->labelCollection = Mage::getModel('hautopart/label')->getCollection();
        $this->labelCollection->addExpressionFieldToSelect('llabel', 'LOWER(label)', 'label');
        $this->labelCollection->getSelect()->having('llabel = ?', $action);
        return ( (count($this->labelCollection) > 0) ? 1 : 0);
    }

    public function isSkuExists($sku,$controller){
        $sku = str_replace('--',' ',$sku);
        $fitmentUrlHelper = Mage::helper('hfitment/url');

        if(!$this->_websiteId){
            $storeCode = $controller->getRequest()->getStoreCodeFromPath();
            $_store = Mage::getModel('core/store')->load($storeCode,'code');
            $webSiteId = $_store->getWebsiteId();
        }else{
            $webSiteId = $this->_websiteId;
        }

        $_product = Mage::getModel('catalog/product')->loadByAttribute('custom_url_key',$sku);

        /** fallback  */
        if(empty($_product)) {
          //  $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
        }

        if($_product && $_product->getId() && $_product->getStatus() == 1){
            return $fitmentUrlHelper->validateSku($_product->getId(),$webSiteId);
        }else{
            return false;
        }
    }
}