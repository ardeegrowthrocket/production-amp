<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26/09/2018
 * Time: 6:28 PM
 */

class Growthrocket_Html_Adminhtml_Html_IndexController extends Mage_Adminhtml_Controller_Action{
    protected function _initAction(){
        $this->loadLayout()
            ->_setActiveMenu('grupdater/grhtmltitle')
            ->_addBreadcrumb(
                Mage::helper('grhtml')->__('Growth Rocket'),
                Mage::helper('grhtml')->__('Growth Rocket')
            )
            ->_addBreadcrumb(
                Mage::helper('grhtml')->__('Dynamic Pages'),
                Mage::helper('grhtml')->__('Dynamic Pages')
            );
        return $this;
    }

    public function indexAction(){
        $this->_initAction();
        $this->renderLayout();
    }
    public function newAction(){
        $this->_forward('edit');
    }
    public function editAction(){
        $this->_title($this->__('Growth Rocket'))
            ->_title($this->__('Dynamic Pages'));
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('grhtml/page');

        if($id){
            $model->load($id);
            if(!$model->getId()){
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('grhtml')->__('Dynamic Page no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_title($model->getId() ? $model->getUrl() : $this->__('New Page'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        Mage::register('grhtml_page', $model);
        $this->_initAction()
            ->_addBreadcrumb(
                Mage::helper('grhtml')->__('New/Update Page'),
                Mage::helper('grhtml')->__('New/Update Page')
            )
            ->_addContent($this->getLayout()->createBlock('grhtml/adminhtml_page_edit'))
            ->renderLayout();
    }
    public function saveAction(){
        if($data = $this->getRequest()->getPost()){
            $model = Mage::getModel('grhtml/page');
            $useDefaultTitle = $this->getRequest()->getParam('use_default_title',null);
            $useDefaultMetaDesc = $this->getRequest()->getParam('use_default_meta_desc',null);
            if(!is_null($useDefaultMetaDesc)){
                $data['meta_desc'] = null;
            }
            if(!is_null($useDefaultTitle)){
                $data['title'] = null;
            }
            $id = $this->getRequest()->getParam('id');

            if($id){
                $model->load($id);
            }


            $model->setData($data);
            try{
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('grhtml')->__('The Page Title has been saved.'));
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
                $model = Mage::getModel('grhtml/page');
                $model->setId($id);
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('grhtml')->__('Dynamic page title has been removed.'));
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
            Mage::helper('sitemap')->__('Unable to find a page to delete.'));
        // go to grid
        $this->_redirect('*/*/');
    }
}