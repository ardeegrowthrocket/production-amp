<?php

class Growthrocket_Newslettertracker_Adminhtml_CaptureController extends Mage_Adminhtml_Controller_Action
{
		protected function _isAllowed()
		{
		//return Mage::getSingleton('admin/session')->isAllowed('newslettertracker/capture');
			return true;
		}

		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("newslettertracker/capture")->_addBreadcrumb(Mage::helper("adminhtml")->__("Capture  Manager"),Mage::helper("adminhtml")->__("Capture Manager"));
				return $this;
		}
		public function indexAction() 
		{
			    $this->_title($this->__("Newslettertracker"));
			    $this->_title($this->__("Manager Capture"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{			    
			    $this->_title($this->__("Newslettertracker"));
				$this->_title($this->__("Capture"));
			    $this->_title($this->__("Edit Item"));
				
				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("newslettertracker/capture")->load($id);
				if ($model->getId()) {
					Mage::register("capture_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("newslettertracker/capture");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Capture Manager"), Mage::helper("adminhtml")->__("Capture Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Capture Description"), Mage::helper("adminhtml")->__("Capture Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("newslettertracker/adminhtml_capture_edit"))->_addLeft($this->getLayout()->createBlock("newslettertracker/adminhtml_capture_edit_tabs"));
					$this->renderLayout();
				} 
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("newslettertracker")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("Newslettertracker"));
		$this->_title($this->__("Capture"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("newslettertracker/capture")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("capture_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("newslettertracker/capture");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Capture Manager"), Mage::helper("adminhtml")->__("Capture Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Capture Description"), Mage::helper("adminhtml")->__("Capture Description"));


		$this->_addContent($this->getLayout()->createBlock("newslettertracker/adminhtml_capture_edit"))->_addLeft($this->getLayout()->createBlock("newslettertracker/adminhtml_capture_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {

						

						$model = Mage::getModel("newslettertracker/capture")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Capture was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setCaptureData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setCaptureData($this->getRequest()->getPost());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					return;
					}

				}
				$this->_redirect("*/*/");
		}



		public function deleteAction()
		{
				if( $this->getRequest()->getParam("id") > 0 ) {
					try {
						$model = Mage::getModel("newslettertracker/capture");
						$model->setId($this->getRequest()->getParam("id"))->delete();
						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item was successfully deleted"));
						$this->_redirect("*/*/");
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					}
				}
				$this->_redirect("*/*/");
		}

		
		public function massRemoveAction()
		{
			try {
				$ids = $this->getRequest()->getPost('ids', array());
				foreach ($ids as $id) {
                      $model = Mage::getModel("newslettertracker/capture");
					  $model->setId($id)->delete();
				}
				Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item(s) was successfully removed"));
			}
			catch (Exception $e) {
				Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
			}
			$this->_redirect('*/*/');
		}
			
		/**
		 * Export order grid to CSV format
		 */
		public function exportCsvAction()
		{
			$fileName   = 'capture.csv';
			$grid       = $this->getLayout()->createBlock('newslettertracker/adminhtml_capture_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
		} 
		/**
		 *  Export order grid to Excel XML format
		 */
		public function exportExcelAction()
		{
			$fileName   = 'capture.xml';
			$grid       = $this->getLayout()->createBlock('newslettertracker/adminhtml_capture_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
		}
}
