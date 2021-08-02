<?php
	
class Homebase_Finder2_Block_Adminhtml_Finder2_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "id";
				$this->_blockGroup = "finder2";
				$this->_controller = "adminhtml_finder2";
				$this->_updateButton("save", "label", Mage::helper("finder2")->__("Save Item"));
				$this->_updateButton("delete", "label", Mage::helper("finder2")->__("Delete Item"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("finder2")->__("Save And Continue Edit"),
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
				if( Mage::registry("finder2_data") && Mage::registry("finder2_data")->getId() ){

				    return Mage::helper("finder2")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("finder2_data")->getId()));

				} 
				else{

				     return Mage::helper("finder2")->__("Add Item");

				}
		}
}