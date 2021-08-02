<?php


class Growthrocket_Cmsblog_Block_Adminhtml_Cmsblog extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_cmsblog";
	$this->_blockGroup = "cmsblog";
	$this->_headerText = Mage::helper("cmsblog")->__("Cmsblog Manager");
	$this->_addButtonLabel = Mage::helper("cmsblog")->__("Add New Item");
	parent::__construct();
	
	}

}