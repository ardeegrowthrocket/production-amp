<?php


class Growthrocket_Newslettertracker_Block_Adminhtml_Capture extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_capture";
	$this->_blockGroup = "newslettertracker";
	$this->_headerText = Mage::helper("newslettertracker")->__("Capture Manager");
	$this->_addButtonLabel = Mage::helper("newslettertracker")->__("Add New Item");
	parent::__construct();
	
	}

}