<?php

class Growthrocket_Faq_Adminhtml_GrfaqController extends Mage_Adminhtml_Controller_Action
{
		protected function _isAllowed()
		{
		//return Mage::getSingleton('admin/session')->isAllowed('faq/grfaq');
			return true;
		}

		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("faq/grfaq")->_addBreadcrumb(Mage::helper("adminhtml")->__("Grfaq  Manager"),Mage::helper("adminhtml")->__("Grfaq Manager"));
				return $this;
		}
		public function indexAction()
		{
			    $this->_title($this->__("Faq"));
			    $this->_title($this->__("Manager Grfaq"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{

			    $this->_title($this->__("Faq"));
				$this->_title($this->__("Grfaq"));
			    $this->_title($this->__("Edit Item"));

				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("faq/grfaq")->load($id);
				if ($model->getId()) {
					Mage::register("grfaq_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("faq/grfaq");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grfaq Manager"), Mage::helper("adminhtml")->__("Grfaq Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grfaq Description"), Mage::helper("adminhtml")->__("Grfaq Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("faq/adminhtml_grfaq_edit"))->_addLeft($this->getLayout()->createBlock("faq/adminhtml_grfaq_edit_tabs"));
					$this->renderLayout();
				}
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("faq")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("Faq"));
		$this->_title($this->__("Grfaq"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("faq/grfaq")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("grfaq_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("faq/grfaq");

            $this->getLayout()->getBlock('head')
                ->setCanLoadExtJs(true)
                ->setCanLoadTinyMce(true)
                ->addItem('js','tiny_mce/tiny_mce.js')
                ->addItem('js','mage/adminhtml/wysiwyg/tiny_mce/setup.js')
                ->addJs('mage/adminhtml/browser.js')
                ->addJs('prototype/window.js')
                ->addJs('lib/flex.js')
                ->addJs('mage/adminhtml/flexuploader.js')
                ->addItem('js_css','prototype/windows/themes/default.css')
                ->addItem('js_css','prototype/windows/themes/magento.css');

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grfaq Manager"), Mage::helper("adminhtml")->__("Grfaq Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grfaq Description"), Mage::helper("adminhtml")->__("Grfaq Description"));


		$this->_addContent($this->getLayout()->createBlock("faq/adminhtml_grfaq_edit"))->_addLeft($this->getLayout()->createBlock("faq/adminhtml_grfaq_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {

						$post_data['store_ids'] = implode(',', $post_data['store_ids']);
						$model = Mage::getModel("faq/grfaq")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Grfaq was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setGrfaqData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					}
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setGrfaqData($this->getRequest()->getPost());
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
						$model = Mage::getModel("faq/grfaq");
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
                      $model = Mage::getModel("faq/grfaq");
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
			$fileName   = 'grfaq.csv';
			$grid       = $this->getLayout()->createBlock('faq/adminhtml_grfaq_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
		}


		public function getPagetypeAction()
        {
            $html = "<option value='0'>Please Select</option>";
            $pageType = $this->getRequest()->getParam("page_type");
            $pageId = $this->getRequest()->getParam("page_id");
            if(!empty($pageType)){
                $model = Mage::getModel("faq/grfaq")->getCollection();
                $model->addFieldToSelect('*');
                $model->addFieldToFilter('page_type', $pageType);
                $model->addFieldToFilter('id', array('neq' => $pageId));
            }

            foreach ($model as $item){
                $selected = "";
                if($pageId == $item->getId()){
                    $selected = "selected";
                }
                $html .= "<option value='{$item->getId()}' {$selected} >{$item->getQuestion()}</option>";
            }

            echo $html;
        }
}
