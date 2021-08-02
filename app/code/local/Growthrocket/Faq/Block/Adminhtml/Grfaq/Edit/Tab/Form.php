<?php
class Growthrocket_Faq_Block_Adminhtml_Grfaq_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
		protected function _prepareForm()
		{

            $form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("faq_form", array("legend"=>Mage::helper("faq")->__("FAQ")));

                        $fieldset->addField("page_type", "select", array(
                            "label" => Mage::helper("faq")->__("Page Type"),
                            "name" => "page_type",
                            "options" =>  Mage::helper('faq')->getPartNames()
                        ));
				
						$fieldset->addField("question", "text", array(
						"label" => Mage::helper("faq")->__("Question"),
						"class" => "required-entry",
						"required" => true,
						"name" => "question",
						));
					
						$fieldset->addField("answer", "editor", array(
						"label" => Mage::helper("faq")->__("Answer"),
						"class" => "required-entry",
						"required" => true,
						"name" => "answer",
                        'wysiwyg' => true,
						));
					
						$fieldset->addField("store_ids", "multiselect", array(
						"label" => Mage::helper("faq")->__("Store"),
						"name" => "store_ids",
                        "class" => "required-entry",
                        "required" => true,
                        'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
						));

					
						$fieldset->addField("status", "select", array(
						"label" => Mage::helper("faq")->__("Status"),
						"name" => "status",
                        'required'  => true,
                        'options'   => array(
                            '1' => Mage::helper('cms')->__('Enabled'),
                            '0' => Mage::helper('cms')->__('Disabled'),
                        ),
						));
					
						$fieldset->addField("parent", "select", array(
						"label" => Mage::helper("faq")->__("Parent"),
						"name" => "parent",
						));
                        $fieldset->addField("position", "text", array(
                            "label" => Mage::helper("faq")->__("Position"),
                            "name" => "position",
                        ));
					

				if (Mage::getSingleton("adminhtml/session")->getGrfaqData())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getGrfaqData());
					Mage::getSingleton("adminhtml/session")->setGrfaqData(null);
				}
				elseif(Mage::registry("grfaq_data")) {
				    $form->setValues(Mage::registry("grfaq_data")->getData());
				}

				echo "<script> var grFaqUrl = '" . Mage::helper('adminhtml')->getUrl('adminhtml/grfaq/getPagetype') . "'  </script>";
				echo "<script> var pageId = '" . Mage::app()->getRequest()->getParam('id') . "'  </script>";

				return parent::_prepareForm();
		}
}
