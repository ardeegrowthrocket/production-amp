<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/27/17
 * Time: 10:26 PM
 */

class Homebase_Autopart_Adminhtml_Hautopart_IndexController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        Mage::getModel('hautopart/option_year');
        $this->renderLayout();
    }

    public function ymAction(){
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
    }

    public function newAction(){
        $grEvent = Mage::helper('grevent');
        $_observer = Mage::getModel('hautopart/fitmentbox');
        if(!$grEvent->hasPendingEvent()){
            $grEvent->queueYmmComboEvent();
            Mage::getSingleton('adminhtml/session')->addSuccess('Request has been queued for processing.');
        }else{
            Mage::getSingleton('adminhtml/session')->addWarning('There is already a running process.');
        }
//        $_observer->build();
        $this->_redirectReferer();
    }

    public function editAction(){
        $this->loadLayout();
        $id = $this->getRequest()->getParam('id');

        $model = Mage::getModel('hautopart/combination');

        if($id){
            $model->load($id);
            if(!$model->getId()){
                $this->_redirect('*/*/');
                return;
            }
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if(!empty($data)){
            $model->setData($data);
        }

        Mage::register('hautopart', $model);

        $this->renderLayout();
    }
    public function saveAction(){
        $_request = $this->getRequest();

        //Zend_Debug::dump($_request->getParams());
    }

    public function customAction(){
        $this->loadLayout();
        $_product = Mage::registry('combination_product');
        $this->renderLayout();
    }
}