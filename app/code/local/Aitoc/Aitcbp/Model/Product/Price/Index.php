<?php
  
class Aitoc_Aitcbp_Model_Product_Price_Index extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('aitcbp/product_price_index');
    }
    
    public function reindexAll()
    {
        $this->_getResource()->reindexAll();
        return $this;
    }

    public function reindexEntity($entityIds)
    {
        $this->_getResource()->reindexEntity($entityIds);
        return $this;
    }

}