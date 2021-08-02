<?php
class Growthrocket_Slider_Block_Adminhtml_Grslider_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
		protected function _prepareForm()
		{

				$form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("slider_form", array("legend"=>Mage::helper("slider")->__("General")));

				
                $fieldset->addField("title", "text", array(
                "label" => Mage::helper("slider")->__("Title"),
                "class" => "required-entry",
                "required" => true,
                "name" => "title",
                ));

                $fieldset->addField("store_ids", "multiselect", array(
                    "label" => Mage::helper("slider")->__("Store View"),
                    "name" => "store_ids",
                    "class" => "required-entry",
                    "required" => true,
                    'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                ));

                $fieldset->addField("is_active", "select", array(
                    "label" => Mage::helper("slider")->__("Status"),
                    "name" => "is_active",
                    'required'  => true,
                    'options'   => array(
                        '1' => Mage::helper('cms')->__('Enabled'),
                        '0' => Mage::helper('cms')->__('Disabled'),
                    ),
                ));

                $fieldset->addField("position", "text", array(
                    "label" => Mage::helper("slider")->__("Order"),
                    "class" => "required-entry",
                    "required" => true,
                    "name" => "position",
                ));

                $fieldset->addField('image', 'image', array(
                    'label' => Mage::helper('slider')->__('Image'),
                    'name' => 'image',
                    'note' => '(*.jpg, *.png, *.gif)',
                ));

                $fieldset->addField("body", "editor", array(
                    "label" => Mage::helper("slider")->__("Content"),
                    "class" => "required-entry",
                    "required" => false,
                    "name" => "body",
                    'wysiwyg' => true,
                ));


                $fieldset->addField("style", "textarea", array(
                "label" => Mage::helper("slider")->__("style"),
                "name" => "style",
                ));
					

				if (Mage::getSingleton("adminhtml/session")->getGrsliderData())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getGrsliderData());
					Mage::getSingleton("adminhtml/session")->setGrsliderData(null);
				}
				elseif(Mage::registry("grslider_data")) {
				    $form->setValues(Mage::registry("grslider_data")->getData());
				}


				return parent::_prepareForm();
		}
}
