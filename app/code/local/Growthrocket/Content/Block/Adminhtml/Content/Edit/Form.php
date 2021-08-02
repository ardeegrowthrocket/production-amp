<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 12:13 AM
 */
class Growthrocket_Content_Block_Adminhtml_Content_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
    public function __construct(){
        parent::__construct();
        $this->setId('adminhtml_content_form');
        $this->setTitle(Mage::helper('grcontent')->__('Insert/Update'));
    }

    protected function _prepareForm(){
        $model = Mage::registry('grcontent_content');

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'method' => 'post',
            'action' => $this->getUrl('*/*/save'),
        ));

        $fieldset = $form->addFieldset('grcontent',array(
            'legend' => Mage::helper('grcontent')->__('Dynamic Content'),
            'class' => 'fieldset-wide'
        ));

        if($model->getId()){
            $fieldset->addField('id', 'hidden',array(
                'name' => 'id'
            ));
        }
        $fieldset->addField('name','text', array(
            'label' => Mage::helper('grcontent')->__('Name'),
            'name' => 'name',
            'required' => true,
            'note' => Mage::helper('grcontent')->__('Dynamic content name'),
            'value' => $model->getName(),
        ));

        $fieldset->addField('content','textarea', array(
            'label' => Mage::helper('grcontent')->__('Dynamic Content'),
            'name' => 'content',
            'required' => true,
            'note' => Mage::helper('grcontent')->__('Dynamic content text'),
            'value' => $model->getContent(),
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