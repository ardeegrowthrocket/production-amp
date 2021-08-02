<?php


class Growthrocket_Slider_Block_Adminhtml_Grslider extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_grslider";
	$this->_blockGroup = "slider";
	$this->_headerText = Mage::helper("slider")->__("Banner Slider Manager");
	$this->_addButtonLabel = Mage::helper("slider")->__("Add New Banner");
	parent::__construct();
	
	}

}