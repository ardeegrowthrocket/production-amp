<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('aitcbp_form', array('legend'=>Mage::helper('aitcbp')->__('General Information')));
		
		$fieldset->addField('rule_name', 'text', array(
			'label'     => Mage::helper('aitcbp')->__('Rule Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'rule_name',
		));
		
		$fieldset->addField('in_groups', 'multiselect', array(
			'label'     => Mage::helper('aitcbp')->__('Group'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'in_groups',
			'values'    => Mage::getModel('aitcbp/group')->getCollection()->toOptionArray(),
		));

		$fieldset->addField('priority', 'text', array(
			'label'     => Mage::helper('aitcbp')->__('Priority'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'priority',
		));
		
		$fieldset->addField('is_active', 'select', array(
			'label'     => Mage::helper('aitcbp')->__('Status'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'is_active',
			'options'    => array(
                '1' => Mage::helper('aitcbp')->__('Active'),
                '0' => Mage::helper('aitcbp')->__('Inactive'),
            ),
		));
		
		if ( Mage::getSingleton('adminhtml/session')->getGroupsData() ) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getGroupsData());
			Mage::getSingleton('adminhtml/session')->getGroupsData(null);
		} 
		else {
			$form->setValues(Mage::registry('aitacbp_rules_data')->getData());			
		}
		
		return parent::_prepareForm();
	}
}
?>