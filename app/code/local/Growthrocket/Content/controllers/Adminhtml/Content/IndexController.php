<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 2:24 PM
 */
class Growthrocket_Content_Adminhtml_Content_IndexController extends Mage_Adminhtml_Controller_Action{
    protected function _initAction(){
        $this->loadLayout()
            ->_setActiveMenu('grupdater/grcontent');
        return $this;
    }
    public function indexAction(){
        $this->_initAction()
            ->renderLayout();
    }

    public function newAction(){
        $this->_forward('edit');
    }

    public function editAction(){
        $this->_title($this->__('Growth Rocket'))
            ->_title($this->__('Dynamic Content'));
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('grcontent/content');

        if($id){
            $model->load($id);
            if(!$model->getId()){
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('grcontent')->__('Dynamic content no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_title($model->getId() ? $model->getName() : $this->__('New Content'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        Mage::register('grcontent_content', $model);
        $this->_initAction()
            ->_addBreadcrumb(
                Mage::helper('grcontent')->__('New/Update Page'),
                Mage::helper('grcontent')->__('New/Update Page')
            )
            ->_addContent($this->getLayout()->createBlock('grcontent/adminhtml_content_edit'))
            ->renderLayout();
    }

    public function saveAction(){
        if($data = $this->getRequest()->getPost()){
            $model = Mage::getModel('grcontent/content');

            $id = $this->getRequest()->getParam('id');

            if($id){
                $model->load($id);
            }
            $model->setData($data);
            try{
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('grcontent')->__('Dynamic Content has been saved.'));
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
        }
        $this->_redirect('*/*/');
    }
    public function deleteAction(){
        $id = $this->getRequest()->getParam('id');
        if($id){
            try{
                $model = Mage::getModel('grcontent/content');
                $model->setId($id);
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('grhtml')->__('Dynamic content has been removed.'));
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