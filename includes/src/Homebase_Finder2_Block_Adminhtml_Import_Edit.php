<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/19/16
 * Time: 4:29 AM
 */

class Homebase_Finder2_Block_Adminhtml_Import_Edit  extends Mage_Adminhtml_Block_Widget_Form_Container{
    public function __construct()
    {

        parent::__construct();
        $this->_objectId = "id";
        $this->_blockGroup = "finder2";
        $this->_controller = "adminhtml_import";
        $this->_removeButton('reset');
    }

    public function getHeaderText(){
       return Mage::helper("finder2")->__("Import Mapping");
    }
}