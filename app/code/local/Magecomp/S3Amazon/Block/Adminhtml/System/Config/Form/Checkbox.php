<?php
class Magecomp_S3Amazon_Block_Adminhtml_System_Config_Form_Checkbox extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('s3amazon/system/config/checkbox.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }
}
