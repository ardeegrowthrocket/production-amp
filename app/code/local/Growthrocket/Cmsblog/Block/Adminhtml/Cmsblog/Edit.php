<?php
	
class Growthrocket_Cmsblog_Block_Adminhtml_Cmsblog_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "id";
				$this->_blockGroup = "cmsblog";
				$this->_controller = "adminhtml_cmsblog";
				$this->_updateButton("save", "label", Mage::helper("cmsblog")->__("Save Item"));
				$this->_updateButton("delete", "label", Mage::helper("cmsblog")->__("Delete Item"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("cmsblog")->__("Save And Continue Edit"),
					"onclick"   => "saveAndContinueEdit()",
					"class"     => "save",
				), -100);



				$this->_formScripts[] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
		}

		public function getHeaderText()
		{
				if( Mage::registry("cmsblog_data") && Mage::registry("cmsblog_data")->getId() ){

				    return Mage::helper("cmsblog")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("cmsblog_data")->getId()));

				} 
				else{

				     return Mage::helper("cmsblog")->__("Add Item");

				}
		}
}