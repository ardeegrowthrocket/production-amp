<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/7/17
 * Time: 10:11 PM
 */

class Homebase_Sitemap_Adminhtml_Multimap_IndexController extends Mage_Adminhtml_Controller_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    public function newAction(){
        $this->_forward('edit');
    }

    public function editAction(){
        $id = $this->getRequest()->getParam('id');

        $model = Mage::getModel('hsitemap/multimap');

        if($id){
            $model->load($id);
            if (! $model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('hsitemap')->__('This sitemap no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_title($model->getId() ? $model->getFilename() : $this->__('New Sitemap'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (! empty($data)) {
            $model->setData($data);
        }

        Mage::register('hsitemap_multimap', $model);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveAction(){
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('hsitemap/multimap');

            if ($this->getRequest()->getParam('id')) {
                $model->load($this->getRequest()->getParam('id'));
            }

            $model->setData($data);
            try {
                $model->save();

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array(
                    'id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function generateAction(){
        $id = $this->getRequest()->getParam('id');
        /** @var Homebase_Sitemap_Model_Multimap $_model */
        $_model = Mage::getModel('hsitemap/multimap');
        $_model->load($id);

        $_model->generateXml();
        $this->_redirect('*/*/');
    }
}
