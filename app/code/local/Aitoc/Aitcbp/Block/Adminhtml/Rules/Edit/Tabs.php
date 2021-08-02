<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('rules_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('aitcbp')->__('Rule Information'));
	}
	
	protected function _beforeToHtml()
	{
		$this->addTab('form_section', array(
			'label'     => Mage::helper('aitcbp')->__('General'),
			'title'     => Mage::helper('aitcbp')->__('General'),
			'content'   => $this->getLayout()->createBlock('aitcbp/adminhtml_rules_edit_tab_form')->toHtml(),
		));
		
		$this->addTab('form_conditions', array(
			'label'     => Mage::helper('aitcbp')->__('Conditions'),
			'title'     => Mage::helper('aitcbp')->__('Conditions'),
			'content'   => $this->getLayout()->createBlock('aitcbp/adminhtml_rules_edit_tab_conditions')->toHtml(),
		));
		
		$this->addTab('form_actions', array(
			'label'     => Mage::helper('aitcbp')->__('Actions'),
			'title'     => Mage::helper('aitcbp')->__('Actions'),
			'content'   => $this->getLayout()->createBlock('aitcbp/adminhtml_rules_edit_tab_actions')->toHtml(),
		));
		
		return parent::_beforeToHtml();
	}
	
}
?>