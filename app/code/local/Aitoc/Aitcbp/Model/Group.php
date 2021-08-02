<?php

class Aitoc_Aitcbp_Model_Group extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('aitcbp/group');
    }

    public function getAsOptions()
    {
        $options      = array('' => Mage::helper('aitcbp')->__('Select'));
        $optionsArray = $this->getCollection()->toOptionArray();
        foreach ($optionsArray as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }

    public function getAssociatedProducts()
    {
        return $this->_getResource()->getAssociatedProductIds($this);
    }

}
