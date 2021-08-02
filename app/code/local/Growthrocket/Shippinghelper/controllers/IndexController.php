<?php
class Growthrocket_Shippinghelper_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Shipping Track"));       
    $shippingInfoModel = Mage::getModel('shipping/info')->loadByHash($_GET['hash']);
        Mage::register('current_shipping_info', $shippingInfoModel);
        if (count($shippingInfoModel->getTrackingInfo()) == 0) {
            // $this->norouteAction();
            // return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }
}