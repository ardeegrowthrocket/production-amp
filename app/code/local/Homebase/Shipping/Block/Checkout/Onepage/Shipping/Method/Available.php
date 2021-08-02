<?php
class Homebase_Shipping_Block_Checkout_Onepage_Shipping_Method_Available extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    public function getShippingRates()
    {

        if (empty($this->_rates)) {
            $this->getAddress()->collectShippingRates()->save();
            $_address = $this->getAddress();
            $regionCode = $_address->getRegionCode();
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
            /*
            if (!empty($groups)) {
                $ratesFilter = new Varien_Filter_Object_Grid();
                $ratesFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'price');

                foreach ($groups as $code => $groupItems) {
                    $groups[$code] = $ratesFilter->filter($groupItems);
                }
            }
            */

            return $this->_rates = $groups;
        }

        return $this->_rates;
    }

}
			