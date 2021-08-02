<?php

class MagicToolbox_Sirv_Block_System_Config_Form_Field_Select_Profile extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $id = $element->getHtmlId();
        $jsString = 'var el = $(\'' . $id . '\'); if(el && el.length < 2) {var row = $(\'row_' . $id . '\'); row.style.display = \'none\'; }';
        return parent::_getElementHtml($element) . $this->helper('adminhtml/js')->getScript('document.observe(\'dom:loaded\', function() {' . $jsString . '});');
    }
}
