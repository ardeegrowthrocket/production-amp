<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 1/27/17
 * Time: 11:15 AM
 */

class Homebase_Shipping_Block_Checkout_Cart_Shipping extends Mage_Checkout_Block_Cart_Shipping{
    public function getEstimateRates()
    {
        if (empty($this->_rates)) {
            /**
             * @var Mage_Sales_Model_Quote_Address $_address
             */
            $_address = $this->getAddress();
            $regionCode = $_address->getRegionCode();
            //echo $regionCode;
            $remove = false;
            if($_address->getCountryId() == 'US'){
                $remove = true;
                if($regionCode == 'PR' || $regionCode == 'AK' || $regionCode == 'GU'){
                    $remove = false;
                }
            }
            $groups = $this->getAddress()->getGroupedAllShippingRates();
            if(array_key_exists('usps', $groups)){
                if($remove){
                    unset($groups['usps']);
                }
            }
            $this->_rates = $groups;
        }
        return $this->_rates;
    }
}
