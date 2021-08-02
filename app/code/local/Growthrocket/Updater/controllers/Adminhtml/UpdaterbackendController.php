<?php
class Growthrocket_Updater_Adminhtml_UpdaterbackendController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        //return Mage::getSingleton('admin/session')->isAllowed('updater/updaterbackend');
        return true;
    }

    public function indexAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    public function validateAction(){
        $data = $this->getRequest()->getPost();
        if(!empty($data)){
            $processor = Mage::getModel('grupdater/csv');
            $filepath = $processor->getUploadedFilePath();
            $itemCount = $processor->process($filepath);
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('%s rows were processed',$itemCount));
        }
        $this->_redirect('*/*/index');
    }
    protected function getWorkingDir(){
        return Mage::getBaseDir('var') . DS . 'massprice' . DS;
    }

    protected function _initAction()
    {
        $this->_title($this->__('Product Price Updater'))
            ->loadLayout()
            ->_setActiveMenu('grupdater/updaterbackend');

        return $this;
    }
}