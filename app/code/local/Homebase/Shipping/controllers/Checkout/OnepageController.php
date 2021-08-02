<?php
require_once "Mage/Checkout/controllers/OnepageController.php";  
class Homebase_Shipping_Checkout_OnepageController extends Mage_Checkout_OnepageController{

    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
}
				