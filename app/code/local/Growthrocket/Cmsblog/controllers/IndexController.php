<?php
class Growthrocket_Cmsblog_IndexController extends Mage_Core_Controller_Front_Action{

    public function preDispatch()
    {
        if(!Mage::helper('cmsblog')->isEnableBlog()){
            $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
            $this->getResponse()->setHeader('Status','404 File not found');
            $this->_forward('defaultNoRoute');
        }
        parent::preDispatch();
    }

    public function IndexAction()
    {
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Blog"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home"),
                "title" => $this->__("Home"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("Blog", array(
                "label" => $this->__("Blog"),
                "title" => $this->__("Blog")
		   ));

      $this->renderLayout();

    }
}