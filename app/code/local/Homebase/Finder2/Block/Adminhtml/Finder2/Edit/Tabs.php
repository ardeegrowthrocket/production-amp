<?php
class Homebase_Finder2_Block_Adminhtml_Finder2_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
		public function __construct()
		{
				parent::__construct();
				$this->setId("finder2_tabs");
				$this->setDestElementId("edit_form");
				$this->setTitle(Mage::helper("finder2")->__("Item Information"));
		}
		protected function _beforeToHtml()
		{
				$this->addTab("form_section", array(
				"label" => Mage::helper("finder2")->__("Item Information"),
				"title" => Mage::helper("finder2")->__("Item Information"),
				"content" => $this->getLayout()->createBlock("finder2/adminhtml_finder2_edit_tab_form")->toHtml(),
				));
				return parent::_beforeToHtml();
		}

}
