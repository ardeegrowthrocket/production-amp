<?php


class Homebase_Finder2_Block_Adminhtml_Finder2 extends Mage_Adminhtml_Block_Widget_Grid_Container{

    public function __construct()
    {

        $this->_controller = "adminhtml_finder2";
        $this->_blockGroup = "finder2";
        $this->_headerText = Mage::helper("finder2")->__("Finder2 Manager");
        $this->_addButtonLabel = Mage::helper("finder2")->__("Add New Item");
        $this->_addButton('importfinder2',array(
            'label' => Mage::helper('finder2')->__('Import Mapping File'),
            'onclick' => "location.href=' "  . $this->getUrl('*/*/import')  . "'",
        ));
        parent::__construct();

    }

}