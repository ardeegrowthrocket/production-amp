<?php
class Growthrocket_Newslettertracker_Block_Adminhtml_Capture_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
		protected function _prepareForm()
		{

				$form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("newslettertracker_form", array("legend"=>Mage::helper("newslettertracker")->__("Item information")));

				
						$fieldset->addField("referrer", "text", array(
						"label" => Mage::helper("newslettertracker")->__("referrer"),					
						"class" => "required-entry",
						"required" => true,
						"name" => "referrer",
						));
					
						$fieldset->addField("email", "text", array(
						"label" => Mage::helper("newslettertracker")->__("Email"),					
						"class" => "required-entry",
						"required" => true,
						"name" => "email",
						));
					
						$fieldset->addField("current", "text", array(
						"label" => Mage::helper("newslettertracker")->__("current"),					
						"class" => "required-entry",
						"required" => true,
						"name" => "current",
						));
					
						$fieldset->addField("ip", "text", array(
						"label" => Mage::helper("newslettertracker")->__("ip"),					
						"class" => "required-entry",
						"required" => true,
						"name" => "ip",
						));
					

				if (Mage::getSingleton("adminhtml/session")->getCaptureData())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getCaptureData());
					Mage::getSingleton("adminhtml/session")->setCaptureData(null);
				} 
				elseif(Mage::registry("capture_data")) {
				    $form->setValues(Mage::registry("capture_data")->getData());
				}
				return parent::_prepareForm();
		}
}
