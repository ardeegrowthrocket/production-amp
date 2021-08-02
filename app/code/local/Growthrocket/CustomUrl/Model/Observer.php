<?php
class Growthrocket_CustomUrl_Model_Observer
{

    public function SetCustomUrlKey(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if(empty($product->getCustomUrlKey())) {
            $sku = $product->getSku();
            $product->setCustomUrlKey($sku);
        }
    }
		
}
