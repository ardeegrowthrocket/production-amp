<?php

class Growthrocket_Cmsblog_Adminhtml_CmsblogController extends Mage_Adminhtml_Controller_Action
{
		protected function _isAllowed()
		{
		//return Mage::getSingleton('admin/session')->isAllowed('cmsblog/cmsblog');
			return true;
		}

		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("cmsblog/cmsblog")->_addBreadcrumb(Mage::helper("adminhtml")->__("Cmsblog  Manager"),Mage::helper("adminhtml")->__("Cmsblog Manager"));
				return $this;
		}
		public function indexAction() 
		{
			    $this->_title($this->__("Cmsblog"));
			    $this->_title($this->__("Manager Cmsblog"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{			    
			    $this->_title($this->__("Cmsblog"));
				$this->_title($this->__("Cmsblog"));
			    $this->_title($this->__("Edit Item"));
				
				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("cmsblog/cmsblog")->load($id);
				if ($model->getId()) {
					Mage::register("cmsblog_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("cmsblog/cmsblog");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Cmsblog Manager"), Mage::helper("adminhtml")->__("Cmsblog Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Cmsblog Description"), Mage::helper("adminhtml")->__("Cmsblog Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("cmsblog/adminhtml_cmsblog_edit"))->_addLeft($this->getLayout()->createBlock("cmsblog/adminhtml_cmsblog_edit_tabs"));
					$this->renderLayout();
				} 
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("cmsblog")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("Cmsblog"));
		$this->_title($this->__("Cmsblog"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("cmsblog/cmsblog")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("cmsblog_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("cmsblog/cmsblog");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Cmsblog Manager"), Mage::helper("adminhtml")->__("Cmsblog Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Cmsblog Description"), Mage::helper("adminhtml")->__("Cmsblog Description"));


		$this->_addContent($this->getLayout()->createBlock("cmsblog/adminhtml_cmsblog_edit"))->_addLeft($this->getLayout()->createBlock("cmsblog/adminhtml_cmsblog_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{
            $postId = $this->getRequest()->getParam("id");
			$post_data=$this->getRequest()->getPost();
            $date = new Zend_Date();

				if ($post_data) {

					try {

					    if(!empty($post_data['store_ids'])){
                            $post_data['store_ids'] = implode(',', $post_data['store_ids']);
                        }

                        if(empty($post_data['identifier'])){
                            $post_data['identifier'] = Mage::helper('cmsblog')->slugify($post_data['title']);
                        }

					    if(empty($postId)){
                            $post_data['created_date'] = $date->get('YYYY-MM-dd HH:mm:ss');
                        }else{
                            $post_data['updated_date'] = $date->get('YYYY-MM-dd HH:mm:ss');
                        }


						$model = Mage::getModel("cmsblog/cmsblog")
						->addData($post_data)
						->setId($postId)
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Cmsblog was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setCmsblogData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setCmsblogData($this->getRequest()->getPost());
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
						$model = Mage::getModel("cmsblog/cmsblog");
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
                      $model = Mage::getModel("cmsblog/cmsblog");
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
			$fileName   = 'cmsblog.csv';
			$grid       = $this->getLayout()->createBlock('cmsblog/adminhtml_cmsblog_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
		} 
		/**
		 *  Export order grid to Excel XML format
		 */
		public function exportExcelAction()
		{
			$fileName   = 'cmsblog.xml';
			$grid       = $this->getLayout()->createBlock('cmsblog/adminhtml_cmsblog_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
		}
}
