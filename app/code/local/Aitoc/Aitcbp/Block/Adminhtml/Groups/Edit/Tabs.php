<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('groups_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('aitcbp')->__('Group Information'));
	}
	
	protected function _beforeToHtml()
	{
		$this->addTab('form_section', array(
			'label'     => Mage::helper('aitcbp')->__('General'),
			'title'     => Mage::helper('aitcbp')->__('General'),
			'content'   => $this->getLayout()->createBlock('aitcbp/adminhtml_groups_edit_tab_form')->toHtml(),
		));
		
		$this->addTab('related', array(
			'label'     => Mage::helper('aitcbp')->__('Group Products'),
			'title'     => Mage::helper('aitcbp')->__('Group Products'),
			'content'   => $this->getLayout()->createBlock('aitcbp/adminhtml_groups_edit_tab_related', 'cbpgroup.product.grid')->toHtml(),
//			'url'       => $this->getUrl('*/*/related', array('_current' => true)),
//            'class'     => 'ajax',
		));
		
		return parent::_beforeToHtml();
	}
	
}
?>