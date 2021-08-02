<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/30/17
 * Time: 6:05 PM
 */
class Homebase_Autopart_Model_Product extends Mage_Catalog_Model_Product{

    public function getProductUrl($useSid = null)
    {
        if($this->getTypeId() == Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID){
            $_helper = Mage::helper('hautopart');
            return strtolower($_helper->getSkuPath($this->_getCustomUrlKey(), null, $this->getStoreId()));
        }else{
            return $this->getUrlModel()->getProductUrl($this, $useSid);
        }
    }

    /**
     * custom product url
     * @return string
     */
    protected function _getCustomUrlKey()
    {
        if(!empty($this->getCustomUrlKey())) {
            return $this->getCustomUrlKey();
        }else {

            /**  Fallback */
            return $this->getSku();
        }
    }

    public function getNewFitment(){
        /** @var Homebase_Fitment_Helper_Data $helper */
        $helper = Mage::helper('hfitment');
        $origFitment = $this->getOrigData('fitment');
        $currentFitment = $this->getData('fitment');
        $allFitment = array_unique(array_merge($origFitment,$currentFitment), SORT_STRING);
        $newFitment = array_diff($allFitment,$origFitment);
        return $newFitment;
    }
    public function getRemovedFitment(){
        /** @var Homebase_Fitment_Helper_Data $helper */
        $helper = Mage::helper('hfitment');
        $origFitment = $this->getOrigData('fitment');
        $currentFitment = $this->getData('fitment');
        $allFitment = array_unique(array_merge($origFitment,$currentFitment), SORT_STRING);
        $removedFitment = array_diff($allFitment,$currentFitment);
        return $removedFitment;
    }
    public function hasFitmentDataChanged(){
        /** @var Homebase_Fitment_Helper_Data $helper */
        $removed = $this->getRemovedFitment();
        $new = $this->getNewFitment();

        if(count($new) > 0 || count($removed) > 0)
            return true;
    }
}