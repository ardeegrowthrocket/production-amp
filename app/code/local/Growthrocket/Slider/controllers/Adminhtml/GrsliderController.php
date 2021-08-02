<?php

class Growthrocket_Slider_Adminhtml_GrsliderController extends Mage_Adminhtml_Controller_Action
{
		protected function _isAllowed()
		{
		//return Mage::getSingleton('admin/session')->isAllowed('slider/grslider');
			return true;
		}

		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("slider/grslider")->_addBreadcrumb(Mage::helper("adminhtml")->__("Grslider  Manager"),Mage::helper("adminhtml")->__("Grslider Manager"));
				return $this;
		}
		public function indexAction()
		{
			    $this->_title($this->__("Slider"));
			    $this->_title($this->__("Manager Grslider"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{
			    $this->_title($this->__("Slider"));
				$this->_title($this->__("Grslider"));
			    $this->_title($this->__("Edit Item"));

				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("slider/grslider")->load($id);
				if ($model->getId()) {
					Mage::register("grslider_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("slider/grslider");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grslider Manager"), Mage::helper("adminhtml")->__("Grslider Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grslider Description"), Mage::helper("adminhtml")->__("Grslider Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("slider/adminhtml_grslider_edit"))->_addLeft($this->getLayout()->createBlock("slider/adminhtml_grslider_edit_tabs"));
					$this->renderLayout();
				}
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("slider")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("Slider"));
		$this->_title($this->__("Grslider"));
		$this->_title($this->__("New New Banner"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("slider/grslider")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("grslider_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("slider/grslider");

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

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grslider Manager"), Mage::helper("adminhtml")->__("Grslider Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Grslider Description"), Mage::helper("adminhtml")->__("Grslider Description"));


		$this->_addContent($this->getLayout()->createBlock("slider/adminhtml_grslider_edit"))->_addLeft($this->getLayout()->createBlock("slider/adminhtml_grslider_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {

						
				 //save image
		try{
            $post_data['store_ids'] = implode(',', $post_data['store_ids']);
if((bool)$post_data['image']['delete']==1) {

	        $post_data['image']='';

}
else {

	unset($post_data['image']);

	if (isset($_FILES)){

		if ($_FILES['image']['name']) {

			if($this->getRequest()->getParam("id")){
				$model = Mage::getModel("slider/grslider")->load($this->getRequest()->getParam("id"));
				if($model->getData('image')){
						$io = new Varien_Io_File();
						$io->rm(Mage::getBaseDir('media').DS.implode(DS,explode('/',$model->getData('image'))));
				}
			}
						$path = Mage::getBaseDir('media') . DS . 'slider' . DS .'grslider'.DS;
						$uploader = new Varien_File_Uploader('image');
						$uploader->setAllowedExtensions(array('jpg','png','gif'));
						$uploader->setAllowRenameFiles(false);
						$uploader->setFilesDispersion(false);
						$destFile = $path.$_FILES['image']['name'];
						$filename = $uploader->getNewFileName($destFile);
						$uploader->save($path, $filename);

						$post_data['image']='slider/grslider/'.$filename;
                        $newPathFilename = $path . $filename;
                        if(!empty($newPathFilename)){
                            $data[] = array(
                                'filename'      => basename($newPathFilename),
                                'content'       => @file_get_contents($newPathFilename),
                                'update_time'   => Mage::getSingleton('core/date')->date(),
                                'directory'     => 'slider/grslider'
                            );

                            $helper = Mage::helper('core/file_storage');
                            $destinationModel = $helper->getStorageModel(Thai_S3_Model_Core_File_Storage::STORAGE_MEDIA_S3);
                            $destinationModel->importFiles($data);
                        }
		}
    }
}

        } catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
        }
//save image


						$model = Mage::getModel("slider/grslider")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Grslider was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setGrsliderData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					}
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setGrsliderData($this->getRequest()->getPost());
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
						$model = Mage::getModel("slider/grslider");
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
                      $model = Mage::getModel("slider/grslider");
					  $model->setId($id)->delete();
				}
				Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item(s) was successfully removed"));
			}
			catch (Exception $e) {
				Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
			}
			$this->_redirect('*/*/');
		}
			
}
