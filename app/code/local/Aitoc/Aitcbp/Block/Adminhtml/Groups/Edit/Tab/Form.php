<?php

class Aitoc_Aitcbp_Block_Adminhtml_Groups_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_customerGroups = null;

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'aitcbp_form', array(
            'legend' => Mage::helper('aitcbp')
                ->__('General Information')
        )
        );

        $fieldset->addField(
            'group_name', 'text', array(
            'label'    => Mage::helper('aitcbp')->__('Group Name'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'group_name',
        )
        );

        $fieldset->addField('in_group_products', 'hidden', array('name' => 'in_group_products'));

        $fieldset->addField(
            'cbp_type', 'select', array(
            'label'    => Mage::helper('aitcbp')->__('Markup Type'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'cbp_type',
            'values'   => Mage::getSingleton('aitcbp/price_type')->getOptionArray(),
        )
        );

        $fieldset->addField(
            'amount', 'text', array(
            'label'    => Mage::helper('aitcbp')->__('Markup Amount'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'amount',
        )
        );

        $fieldset->addField(
            'is_active', 'select', array(
            'label'    => Mage::helper('aitcbp')->__('Status'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'is_active',
            'options'  => array(
                '1' => Mage::helper('aitcbp')->__('Active'),
                '0' => Mage::helper('aitcbp')->__('Inactive'),
            ),
        )
        );
        $fieldset->addField(
            'is_round', 'select', array(
            'label'    => Mage::helper('aitcbp')->__('Round to nearest integer'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'is_round',
            'options'  => array(
                '1' => Mage::helper('aitcbp')->__('Active'),
                '0' => Mage::helper('aitcbp')->__('Inactive'),
            ),
        )
        );

        if (Mage::getSingleton('adminhtml/session')->getGroupsData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getGroupsData());
            Mage::getSingleton('adminhtml/session')->getGroupsData(null);
        } else {
            $form->setValues(Mage::registry('aitacbp_groups_data')->getData());
        }

        return parent::_prepareForm();
    }

    public function getCustomerGroups()
    {
        if (is_null($this->_customerGroups)) {
            $this->_customerGroups = Mage::getModel('customer/group')->getCollection()->toOptionArray();
        }

        return $this->_customerGroups;
    }
}
