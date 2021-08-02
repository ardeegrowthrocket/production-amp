<?php
class Magecomp_S3Amazon_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{   
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('s3amazon/system/config/button.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('s3amazon/adminhtml_s3Amazon/check');
    }
 
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
            'id'        => 's3amazon_button',
            'label'     => $this->helper('adminhtml')->__('Check Bucket Availabilty'),
            'onclick'   => 'javascript:validateData(); return false;'
        )); 
        return $button->toHtml();
    }
}
