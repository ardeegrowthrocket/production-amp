<?php
class Growthrocket_Newslettertracker_Block_Adminhtml_Capture_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
		public function __construct()
		{
				parent::__construct();
				$this->setId("capture_tabs");
				$this->setDestElementId("edit_form");
				$this->setTitle(Mage::helper("newslettertracker")->__("Item Information"));
		}
		protected function _beforeToHtml()
		{
				$this->addTab("form_section", array(
				"label" => Mage::helper("newslettertracker")->__("Item Information"),
				"title" => Mage::helper("newslettertracker")->__("Item Information"),
				"content" => $this->getLayout()->createBlock("newslettertracker/adminhtml_capture_edit_tab_form")->toHtml(),
				));
				return parent::_beforeToHtml();
		}

}
