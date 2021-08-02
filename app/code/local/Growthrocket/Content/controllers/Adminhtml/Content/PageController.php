<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 3:09 PM
 */

class Growthrocket_Content_Adminhtml_Content_PageController extends Mage_Adminhtml_Controller_Action{
    protected function _initAction(){
        $this->loadLayout()
            ->_setActiveMenu('grupdater/grcontent');
        $this->_title($this->__('Growth Rocket'))
            ->_title($this->__('Content Template'));
        return $this;
    }
    public function indexAction(){
        $this->_initAction()
            ->_title($this->__('Pages'))
            ->renderLayout();
    }
    public function newAction(){
        $this->_forward('edit');
    }
    public function editAction(){
        $this->_initAction();
        $this->_title($this->__('Pages'));
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('grcontent/page');
        if($id){
            $model->load($id);
            if(!$model->getId()){
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('grcontent')->__('Page no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_title($model->getId() ? $model->getUrl() : $this->__('New Page'));
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('grcontent_page', $model);
        $this->_addBreadcrumb(
            Mage::helper('grcontent')->__('New/Update Page'),
            Mage::helper('grcontent')->__('New/Update Page')
        )
            ->_addContent($this->getLayout()->createBlock('grcontent/adminhtml_page_edit'))
            ->renderLayout();
    }

    public function saveAction(){
        if($data = $this->getRequest()->getPost()){
            $model = Mage::getModel('grcontent/page');

            $id = $this->getRequest()->getParam('id');

            if($id){
                $model->load($id);
            }
            $model->setData($data);

            try{
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('grcontent')->__('Page has been saved.'));
                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                $this->_redirect('*/*/');
                return;
            }catch(Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array(
                    'id' => $this->getRequest()->getParam('id')));
                return;
            }
            $this->_redirect('*/*/');
        }
    }
    public function deleteAction(){
        $id = $this->getRequest()->getParam('id');
        if($id){
            try{
                $model = Mage::getModel('grcontent/page');
                $model->setId($id);
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('grhtml')->__('Page has been removed.'));
                $this->_redirect('*/*/');
                return;
            }catch(Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // go back to edit form
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('grcontent')->__('Unable to find a content to delete.'));
        // go to grid
        $this->_redirect('*/*/');
    }
}