<?php
class Growthrocket_Cmsblog_Block_Adminhtml_Cmsblog_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
		public function __construct()
		{
				parent::__construct();
				$this->setId("cmsblog_tabs");
				$this->setDestElementId("edit_form");
				$this->setTitle(Mage::helper("cmsblog")->__("Item Information"));
		}
		protected function _beforeToHtml()
		{
				$this->addTab("form_section", array(
				"label" => Mage::helper("cmsblog")->__("Item Information"),
				"title" => Mage::helper("cmsblog")->__("Item Information"),
				"content" => $this->getLayout()->createBlock("cmsblog/adminhtml_cmsblog_edit_tab_form")->toHtml(),
				));
				return parent::_beforeToHtml();
		}

}
