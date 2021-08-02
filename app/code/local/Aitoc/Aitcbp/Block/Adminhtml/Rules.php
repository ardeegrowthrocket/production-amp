<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_rules';
		$this->_blockGroup = 'aitcbp';
		$this->_headerText = Mage::helper('aitcbp')->__('Manage Automatic Price Rules');
		$this->_addButtonLabel = Mage::helper('aitcbp')->__('Add Rule');
		parent::__construct();
	}
	
	
}
?>