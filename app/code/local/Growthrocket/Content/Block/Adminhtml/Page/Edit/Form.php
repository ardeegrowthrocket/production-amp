<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:47 PM
 */

class Growthrocket_Content_Block_Adminhtml_Page_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
    public function __construct(){
        parent::__construct();
        $this->setId('adminhtml_page_form');
        $this->setTitle(Mage::helper('grcontent')->__('Insert/Update'));
    }
    protected function _prepareForm(){
        $model = Mage::registry('grcontent_page');

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'method' => 'post',
            'action' => $this->getUrl('*/*/save'),
        ));

        $fieldset = $form->addFieldset('grcontent',array(
            'legend' => Mage::helper('grcontent')->__('Page'),
            'class' => 'fieldset-wide'
        ));
        if($model->getId()){
            $fieldset->addField('id', 'hidden',array(
                'name' => 'id'
            ));
        }
        $fieldset->addField('type','text', array(
            'label' => Mage::helper('grcontent')->__('Page Category'),
            'name' => 'type',
            'required' => true,
            'note' => Mage::helper('grcontent')->__('Page Block Type'),
            'value' => $model->getType(),
        ));
        $fieldset->addField('url','text', array(
            'label' => Mage::helper('grcontent')->__('Page Request Url'),
            'name' => 'url',
            'required' => true,
            'note' => Mage::helper('grcontent')->__('Request Url'),
            'value' => $model->getUrl(),
        ));
        $fieldset->addField('content_id','text', array(
            'label' => Mage::helper('grcontent')->__('Page Dynamic Content'),
            'name' => 'content_id',
            'required' => true,
            'note' => Mage::helper('grcontent')->__('Dynamic Content'),
            'value' => $model->getUrl(),
        ));

        $fieldset->addField('store_id', 'select', array(
            'label' => Mage::helper('grcontent')->__('Store Id'),
            'name' => 'store_id',
            'required' => true,
            'note'  => Mage::helper('grcontent')->__("Store ID"),
            'value'    => $model->getStoreId(),
            'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
        ));
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}