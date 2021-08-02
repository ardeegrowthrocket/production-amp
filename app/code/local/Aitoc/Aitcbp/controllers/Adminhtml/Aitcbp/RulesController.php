<?php

class Aitoc_Aitcbp_Adminhtml_Aitcbp_RulesController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() 
	{
		$this->loadLayout()
			->_setActiveMenu('catalog/aitcbp')
			->_addBreadcrumb(Mage::helper('aitcbp')->__('Rules manager'), Mage::helper('aitcbp')->__('Rules manager'))
			->_title(Mage::helper('aitcbp')->__('Catalog'))
			->_title(Mage::helper('aitcbp')->__('Cost Based Price'))
			->_title(Mage::helper('aitcbp')->__('Manage Automatic Price Rules'))
			;
		return $this;
	}
	
	public function indexAction()
	{
		$this->_initAction()
			->renderLayout();
	}
	
	public function newAction()
	{
		$this->_forward('edit');
	}
	
	public function editAction()
	{
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('aitcbp/rule')->load($id);
		
		if ($model->getId() || $id == 0) {

			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			
			if (!empty($data)) 
			{
				$model->setData($data);
			}
			
			Mage::register('aitacbp_rules_data', $model);
			
			$this->_initAction();
			if ($model->getRuleName()) {
				$this->_title($model->getRuleName());
			} else {
				$this->_title(Mage::helper('aitcbp')->__('New Rule'));
			}
			$this->renderLayout();		
		} 
		else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('aitcbp')->__('Rule does not exist'));
			$this->_redirect('*/*/');
		}
	}
	
	public function saveAction()
	{
		if ($data = $this->getRequest()->getPost()) {
			$model = Mage::getModel('aitcbp/rule');		
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
				
			try {
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('aitcbp')->__('Rule was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);
				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
			}
		}
	}
	
	public function deleteAction()
	{
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel('aitcbp/rule');
				$model->setId($this->getRequest()->getParam('id'))->delete();
				
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
				$this->_redirect('*/*/');
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}
}
?>