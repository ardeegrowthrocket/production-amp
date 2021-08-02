<?php
class Aitoc_Aitcbp_Model_Price_Type extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	const TYPE_SELECT	= 0;
	const TYPE_FIXED	= 1;
    const TYPE_PERCENT	= 2;
    
	protected function _construct()
    {
        $this->_init('aitcbp/price_type');
    }
    
	static public function getOptionArray()
    {
        return array(
        	//self::TYPE_SELECT   => Mage::helper('aitcbp')->__('Select'),
            self::TYPE_FIXED    => Mage::helper('aitcbp')->__('Fixed'),
            self::TYPE_PERCENT   => Mage::helper('aitcbp')->__('Percent'),
        );
    }
    
	public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('aitcbp')->__('Select'),
                    'value' =>  self::TYPE_SELECT
                ),
                array(
                    'label' => Mage::helper('aitcbp')->__('Fixed'),
                    'value' =>  self::TYPE_FIXED
                ),
                array(
                    'label' => Mage::helper('aitcbp')->__('Percent'),
                    'value' =>  self::TYPE_PERCENT
                ),
            );
        }
        return $this->_options;
    }
    
	public function setAttribute($attribute)
    {
        $this->_attribute = $attribute;
        return $this;
    }
}
