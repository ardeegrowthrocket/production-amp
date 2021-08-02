<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/19/16
 * Time: 4:19 AM
 */
class Homebase_Finder2_Adminhtml_Finder2_IndexController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu("finder2/finder2")->_addBreadcrumb(Mage::helper("adminhtml")->__("Finder2  Manager"),Mage::helper("adminhtml")->__("Finder2 Manager"));
        return $this;
    }
    public function indexAction()
    {
        $this->_title($this->__("Finder2"));
        $this->_title($this->__("Manager Finder2"));

        $this->_initAction();
        $this->renderLayout();
    }

    public function importAction(){
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('finder2/adminhtml_import_edit');
        $this->_addContent($block)->renderLayout();
    }

    public function saveimportAction(){
        if(isset($_FILES['mapping_file'])){
            $file = file_get_contents($_FILES['mapping_file']['tmp_name']);
            $lines = explode(PHP_EOL, $file);
            $records = array();
            foreach($lines as $line){
                $records[]  = str_getcsv($line);
            }
            foreach ($records as $record){
                if(count($record) == 4){
                    $model  = Mage::getModel('finder2/finder2');
                    $_collection = Mage::getModel('finder2/finder2')->getCollection();
                    $model->setYear($record[0]);
                    $model->setMake($record[1]);
                    $model->setModel($record[2]);
                    $model->setCategory($record[3]);
                    $model->save();
                }
            }
        }
    }

    public function editAction()
    {
        $this->_title($this->__("Finder2"));
        $this->_title($this->__("Finder2"));
        $this->_title($this->__("Edit Item"));

        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("finder2/finder2")->load($id);
        if ($model->getId()) {
            Mage::register("finder2_data", $model);
            $this->loadLayout();
            $this->_setActiveMenu("finder2/finder2");
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Finder2 Manager"), Mage::helper("adminhtml")->__("Finder2 Manager"));
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Finder2 Description"), Mage::helper("adminhtml")->__("Finder2 Description"));
            $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock("finder2/adminhtml_finder2_edit"))->_addLeft($this->getLayout()->createBlock("finder2/adminhtml_finder2_edit_tabs"));
            $this->renderLayout();
        }
        else {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("finder2")->__("Item does not exist."));
            $this->_redirect("*/*/");
        }
    }

    public function newAction()
    {

        $this->_title($this->__("Finder2"));
        $this->_title($this->__("Finder2"));
        $this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
        $model  = Mage::getModel("finder2/finder2")->load($id);

        $data = Mage::getSingleton("adminhtml/session")->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register("finder2_data", $model);

        $this->loadLayout();
        $this->_setActiveMenu("finder2/finder2");

        $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

        $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Finder2 Manager"), Mage::helper("adminhtml")->__("Finder2 Manager"));
        $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Finder2 Description"), Mage::helper("adminhtml")->__("Finder2 Description"));


        $this->_addContent($this->getLayout()->createBlock("finder2/adminhtml_finder2_edit"))->_addLeft($this->getLayout()->createBlock("finder2/adminhtml_finder2_edit_tabs"));

        $this->renderLayout();

    }
    public function saveAction()
    {

        $post_data=$this->getRequest()->getPost();


        if ($post_data) {

            try {



                $model = Mage::getModel("finder2/finder2")
                    ->addData($post_data)
                    ->setId($this->getRequest()->getParam("id"))
                    ->save();

                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Finder2 was successfully saved"));
                Mage::getSingleton("adminhtml/session")->setFinder2Data(false);

                if ($this->getRequest()->getParam("back")) {
                    $this->_redirect("*/*/edit", array("id" => $model->getId()));
                    return;
                }
                $this->_redirect("*/*/");
                return;
            }
            catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                Mage::getSingleton("adminhtml/session")->setFinder2Data($this->getRequest()->getPost());
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
                $model = Mage::getModel("finder2/finder2");
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
                $model = Mage::getModel("finder2/finder2");
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
        $fileName   = 'finder2.csv';
        $grid       = $this->getLayout()->createBlock('finder2/adminhtml_finder2_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName   = 'finder2.xml';
        $grid       = $this->getLayout()->createBlock('finder2/adminhtml_finder2_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}
