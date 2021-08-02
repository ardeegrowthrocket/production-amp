<?php
	
class Growthrocket_Newslettertracker_Block_Adminhtml_Capture_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "id";
				$this->_blockGroup = "newslettertracker";
				$this->_controller = "adminhtml_capture";
				$this->_updateButton("save", "label", Mage::helper("newslettertracker")->__("Save Item"));
				$this->_updateButton("delete", "label", Mage::helper("newslettertracker")->__("Delete Item"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("newslettertracker")->__("Save And Continue Edit"),
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
				if( Mage::registry("capture_data") && Mage::registry("capture_data")->getId() ){

				    return Mage::helper("newslettertracker")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("capture_data")->getId()));

				} 
				else{

				     return Mage::helper("newslettertracker")->__("Add Item");

				}
		}
}