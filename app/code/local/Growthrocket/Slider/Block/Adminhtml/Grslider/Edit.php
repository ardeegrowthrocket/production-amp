<?php

class Growthrocket_Slider_Block_Adminhtml_Grslider_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "id";
				$this->_blockGroup = "slider";
				$this->_controller = "adminhtml_grslider";
				$this->_updateButton("save", "label", Mage::helper("slider")->__("Save Item"));
				$this->_updateButton("delete", "label", Mage::helper("slider")->__("Delete Item"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("slider")->__("Save And Continue Edit"),
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
				if( Mage::registry("grslider_data") && Mage::registry("grslider_data")->getId() ){

				    return Mage::helper("slider")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("grslider_data")->getId()));

				}
				else{

				     return Mage::helper("slider")->__("Add Item");

				}
		}
}