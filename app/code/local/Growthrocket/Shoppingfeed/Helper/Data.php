<?php
class Growthrocket_Shoppingfeed_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isEnableFilterProduct()
    {
        return (bool) Mage::getStoreConfig('shopping_feed/sf_info/filter_product_status', 0);
    }
}
	 