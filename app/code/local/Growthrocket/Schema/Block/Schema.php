<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/12/18
 * Time: 11:02 AM
 */

class Growthrocket_Schema_Block_Schema extends Mage_Core_Block_Abstract{
    const CONTEXT = 'http://schema.org';
    const IS_ENABLE_PART_LISTING = 'porto_settings/richsnippets/part_listing';

    protected function _construct(){

    }
    protected function _toHtml(){
        $html = '';
        $isEnableInPartListing = Mage::getStoreConfig(self::IS_ENABLE_PART_LISTING);
        if($this->isAllowed() && !$isEnableInPartListing){
            $html = '<script type="application/ld+json">' . $this->getSchema() . '</script>';
        }
        return $html;
    }
    protected function getSchema(){
        return;
    }
    public function isAllowed(){
        return true;
    }

    /**
     * @return Mage_Core_Model_Store
     */
    public function getStore(){
        $storeCode = $this->getRequest()->getStoreCodeFromPath();
        $_store = Mage::getModel('core/store')->load($storeCode, 'code');
        return $_store;
    }
    public function getCurrencyCode(){
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }
}