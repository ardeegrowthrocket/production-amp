<?php
class Growthrocket_Bestseller_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param $price
     * @return mixed
     */
    public function formatPrice($price)
    {
        return Mage::helper('core')->currency($price, true, false);
    }
}
	 