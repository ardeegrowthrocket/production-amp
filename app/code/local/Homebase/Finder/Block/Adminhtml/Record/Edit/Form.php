<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/5/16
 * Time: 5:45 PM
 */

class Homebase_Finder_Block_Adminhtml_Record_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
    public function __construct()
    {
        parent::__construct();
        $this->setId('adminhtml_record_form');
        $this->setTitle(Mage::helper('hfinder')->__('Upload'));
    }
    protected function _prepareForm()
    {

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'method' => 'post',
            'action'    => $this->getUrl('*/*/save'),
            'enctype' => 'multipart/form-data'
        ));
        $fieldset = $form->addFieldset('hfinder_mapper', array(
            'legend'    => Mage::helper('hfinder')->__('File Mapping'),
            'class'     => 'fieldset-wide',
        ));

        $fieldset->addField('mappin_file', 'file', array(
            'label' => Mage::helper('hfinder')->__('Filename'),
            'name'  => 'mapping_file',
            'required' => true,
            'note'  => Mage::helper('adminhtml')->__('example: mapping.csv'),

        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm(); // TODO: Change the autogenerated stub
    }
}