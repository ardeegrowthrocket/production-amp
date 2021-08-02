<?php
class Growthrocket_Cmsblog_CmsblogController extends Mage_Core_Controller_Front_Action
{

    public function preDispatch()
    {
        if(!Mage::helper('cmsblog')->isEnableBlog()){
            $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
            $this->getResponse()->setHeader('Status','404 File not found');
            $this->_forward('defaultNoRoute');
        }
        parent::preDispatch();
    }

    public function indexAction()
    {

        $this->loadLayout();
        $this->renderLayout();
    }
}