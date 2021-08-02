<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/22/17
 * Time: 2:44 AM
 */

class Homebase_Autopart_ModelController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    public function modelAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    public function ymmAction(){
        $_request = $this->getRequest();
        $this->loadLayout();
//        $_cookie = Mage::getSingleton('core/cookie');
//        if($_cookie->get('qymm') == 1){
            //Mage::getSingleton('core/session')->setData('q',1);
//        }
        $this->renderLayout();
    }

    public function skuAction(){
        /** @var Mage_Core_Controller_Request_Http  $query */
        $requestString = $this->getRequest()->getRequestString();
        $requestString = substr($requestString,1);
        $results = array();
        preg_match('/\/(.*?)\./',$requestString,$results);

        if(preg_match('/[A-Z]/', $requestString)){
            $lowercase = strtolower(Mage::getBaseUrl(). $requestString);
            $this->getResponse()->setRedirect($lowercase,301);
        }

        if(empty($results)){
            $this->getResponse()->setRedirect(Mage::getBaseUrl(),301);
            return;
        } 

        $sku = $results[1];
        $sku = str_replace('--',' ',$sku);

        $_product = Mage::getModel('catalog/product')->loadByAttribute('custom_url_key',$sku);

        /** Fallback */
        if(empty($_product)) {
            $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
        }

        $productHelper = Mage::helper('catalog/product');
        $productHelper->initProduct($_product->getId(), $this, array());
        $this->loadLayout();
        $this->renderLayout();
    }
    public function ymmsAction(){
        /** @var  Mage_Core_Controller_Request_Http $request */
        $request = unserialize($this->getRequest()->getParam('ymm_params'));
        $sku = $request['sku'];
        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
        $productHelper = Mage::helper('catalog/product');
        $productHelper->initProduct($_product->getId(), $this, array());
        $this->loadLayout();
        $this->renderLayout();
    }
    public function catAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}
