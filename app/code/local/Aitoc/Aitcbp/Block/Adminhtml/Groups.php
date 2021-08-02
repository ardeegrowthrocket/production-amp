<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_groups';
		$this->_blockGroup = 'aitcbp';
		$this->_headerText = Mage::helper('aitcbp')->__('Manage Groups');
		$this->_addButtonLabel = Mage::helper('aitcbp')->__('Add Group');
		parent::__construct();
	}
	
	
}
?>