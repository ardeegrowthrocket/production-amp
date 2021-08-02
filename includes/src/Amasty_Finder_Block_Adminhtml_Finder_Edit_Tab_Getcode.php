<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */ 
class Amasty_Finder_Block_Adminhtml_Finder_Edit_Tab_Getcode extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        
        /* @var $hlp Amasty_Finder_Helper_Data */
        $hlp   = Mage::helper('amfinder');
        $model = Mage::registry('amfinder_finder');
        
        $fldInfo = $form->addFieldset('general', array('legend'=> $hlp->__('Get Finder Code')));
        
        $fldInfo->addField('getcode', 'textarea', array(
            'label'     => $hlp->__('Code'),
            'name'      => 'getcode',
            'value'     => '{{block type="amfinder/form" id="' . $model->getId() . '"}}',
            'note'      =>
                $hlp->__('Use this code in cms pages, for more details see '
                    . '<a target="blank" href="https://amasty.com/media/user_guides/product_parts_finder_user_guide.pdf" >'
                    . 'user guide link</a>')
        ));

        return parent::_prepareForm();
    }
}
