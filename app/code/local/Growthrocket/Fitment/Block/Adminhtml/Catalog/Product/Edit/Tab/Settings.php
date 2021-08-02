<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15/05/2018
 * Time: 9:43 AM
 */

class Growthrocket_Fitment_Block_Adminhtml_Catalog_Product_Edit_Tab_Settings extends Mage_Adminhtml_Block_Widget_Form{
    protected function _prepareLayout()
    {
        $this->setChild('continue_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('catalog')->__('Continue'),
                    'onclick'   => "setSettings('".$this->getContinueUrl()."','attribute_set_id','product_type','website_id')",
                    'class'     => 'save'
                ))
        );
        return parent::_prepareLayout();
    }
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('settings', array('legend'=>Mage::helper('catalog')->__('Create Product Settings xxx')));

        $entityType = Mage::registry('product')->getResource()->getEntityType();

        $fieldset->addField('attribute_set_id', 'select', array(
            'label' => Mage::helper('catalog')->__('Attribute Set'),
            'title' => Mage::helper('catalog')->__('Attribute Set'),
            'name'  => 'set',
            'value' => $entityType->getDefaultAttributeSetId(),
            'values'=> Mage::getResourceModel('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter($entityType->getId())
                ->load()
                ->toOptionArray()
        ));

        $fieldset->addField('product_type', 'select', array(
            'label' => Mage::helper('catalog')->__('Product Type'),
            'title' => Mage::helper('catalog')->__('Product Type'),
            'name'  => 'type',
            'value' => '',
            'values'=> Mage::getModel('catalog/product_type')->getOptionArray()
        ));

        $fieldset->addField('website_id', 'select', array(
            'label' => Mage::helper('catalog')->__('Website'),
            'title' => Mage::helper('catalog')->__('Website'),
            'name' => 'website',
            'value' => '',
            'values' => Mage::getModel('grfitment/website')->getOptionArray()
        ));

        $fieldset->addField('continue_button', 'note', array(
            'text' => $this->getChildHtml('continue_button'),
        ));

        $this->setForm($form);
    }
    public function getContinueUrl()
    {
        return $this->getUrl('*/*/new', array(
            '_current'  => true,
            'set'       => '{{attribute_set}}',
            'type'      => '{{type}}',
            'website'     => '{{website}}'
        ));
    }

}