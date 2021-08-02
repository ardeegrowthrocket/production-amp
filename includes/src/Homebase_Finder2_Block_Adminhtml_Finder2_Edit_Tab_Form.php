<?php
class Homebase_Finder2_Block_Adminhtml_Finder2_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
		protected function _prepareForm()
		{

				$form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("finder2_form", array("legend"=>Mage::helper("finder2")->__("Item information")));

				
						$fieldset->addField("year", "text", array(
						"label" => Mage::helper("finder2")->__("Year"),
						"name" => "year",
						));
					
						$fieldset->addField("make", "text", array(
						"label" => Mage::helper("finder2")->__("Make"),
						"name" => "make",
						));
					
						$fieldset->addField("model", "text", array(
						"label" => Mage::helper("finder2")->__("Model"),
						"name" => "model",
						));
					
						$fieldset->addField("category", "text", array(
						"label" => Mage::helper("finder2")->__("Category Reference"),
						"name" => "category",
						));
					

				if (Mage::getSingleton("adminhtml/session")->getFinder2Data())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getFinder2Data());
					Mage::getSingleton("adminhtml/session")->setFinder2Data(null);
				} 
				elseif(Mage::registry("finder2_data")) {
				    $form->setValues(Mage::registry("finder2_data")->getData());
				}
				return parent::_prepareForm();
		}
}
