<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26/09/2018
 * Time: 10:10 PM
 */

class Growthrocket_Html_Block_Adminhtml_Page_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
    public function __construct(){
        parent::__construct();
        $this->setId('adminhtml_page_form');
        $this->setTitle(Mage::helper('grhtml')->__('Save'));
    }
    protected function _prepareForm(){

        $model = Mage::registry('grhtml_page');
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'method' => 'post',
            'action' => $this->getUrl('*/*/save'),
        ));


        $fieldset = $form->addFieldset('grhtml_page',array(
            'legend' => Mage::helper('grhtml')->__('Page HTML Title'),
            'class' => 'fieldset-wide'
        ));
        $fieldset->addType('textcheck','Growthrocket_Html_Block_Adminhtml_Input_Renderer_Textcheck');
        if($model->getId()){
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }
        $fieldset->addField('module', 'text', array(
            'label' => Mage::helper('grhtml')->__('Module Name'),
            'name' => 'module',
            'required' => true,
            'note'  => Mage::helper('grhtml')->__('Module name where the route is under'),
            'value' => $model->getModule()
        ));
        $fieldset->addField('url', 'text', array(
            'label' => Mage::helper('grhtml')->__('Request String'),
            'name' => 'url',
            'required' => true,
            'note'  => Mage::helper('grhtml')->__('Ex. make/xx.html'),
            'value' => $model->getUrl()
        ));
        $fieldset->addField('title','textcheck', array(
            'label' => Mage::helper('grhtml')->__('Page Title'),
            'name' => 'title',
            'required' => false,
            'note'  => Mage::helper('grhtml')->__("Value for the title tag"),
            'value' => $model->getTitle(),
        ));
        $fieldset->addField('meta_desc','textcheck', array(
            'label' => Mage::helper('grhtml')->__('Page Meta Description'),
            'name' => 'meta_desc',
            'required' => false,
            'note'  => Mage::helper('grhtml')->__("Value for the meta description tag"),
            'value' => $model->getMetaDesc(),
        ));
        $fieldset->addField('store_id', 'select', array(
            'label' => Mage::helper('grhtml')->__('Store Id'),
            'name' => 'store_id',
            'required' => true,
            'note'  => Mage::helper('grhtml')->__("Store ID"),
            'value'    => $model->getStoreId(),
            'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
        ));
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}