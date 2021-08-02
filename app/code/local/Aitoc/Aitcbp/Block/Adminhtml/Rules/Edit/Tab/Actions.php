<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Edit_Tab_Actions extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('aitcbp_actions', array('legend'=>Mage::helper('aitcbp')->__('Actions')));
		
		$fieldset->addField('action_value', 'select', array(
			'label'     => Mage::helper('aitcbp')->__('Action'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'action_value',
			'options'    => array(
                '' => Mage::helper('aitcbp')->__('Select'),
                '1' => Mage::helper('aitcbp')->__('Increase Markup'),
				'2' => Mage::helper('aitcbp')->__('Decrease Markup'),
            ),
			/*
			'options'    => array(
                '' => Mage::helper('aitcbp')->__('Select'),
                '1' => Mage::helper('aitcbp')->__('Increase Percent Markup'),
				'2' => Mage::helper('aitcbp')->__('Decrease Percent Markup'),
				'3' => Mage::helper('aitcbp')->__('Increase Fixed Markup'),
				'4' => Mage::helper('aitcbp')->__('Decrease Fixed Markup'),
            ),*/
		));

		$fieldset->addField('increase_decrease', 'text', array(
			'label'     => Mage::helper('aitcbp')->__('Increase/decrease by '),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'increase_decrease',
		));
		
		$fieldset->addField('futher_rules', 'select', array(
			'label'     => Mage::helper('aitcbp')->__('Stop further rules processing'),
			'class'     => 'required-entry',
			'name'      => 'futher_rules',
			'options'    => array(
                '1' => Mage::helper('aitcbp')->__('Yes'),
                '0' => Mage::helper('aitcbp')->__('No'),
            ),
		));
		
		if ( Mage::getSingleton('adminhtml/session')->getRuleData() ) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getRuleData());
			Mage::getSingleton('adminhtml/session')->getRuleData(null);
		} 
		else {
			$form->setValues(Mage::registry('aitacbp_rules_data')->getData());
		}
		
		return parent::_prepareForm();
	}
}
?>