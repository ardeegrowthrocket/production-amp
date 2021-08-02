<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 07/06/2018
 * Time: 1:58 PM
 */

class Growthrocket_Updater_Block_Adminhtml_Import_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
    protected function _prepareForm(){
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/validate'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $fieldset = $form->addFieldset('base_fieldset',array('legend' => Mage::helper('grupdater')->__('Import')));
        $fieldset->addField('fileupload', 'file',array(
            'name' => 'fileupload',
            'label' => Mage::helper('grupdater')->__('File Upload'),
            'title' => Mage::helper('grupdater')->__('File Upload'),
            'after_element_html' => Mage::helper('grupdater')->__('<p>.csv with sku,cost,msrp columns</p>'),
            'required' => true,
        ));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}