<?php

class Pektsekye_PartFinder_Model_Mysql4_Attribute extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {    
        $this->_init('eav/attribute', 'attribute_id');
    }  



    public function findProducts($attributeId, $storeId, $optionIds = array())
    {
        $select = $this->_getReadAdapter()->select()         
          ->from($this->getTable('catalog/product_index_eav'), 'entity_id')
          ->where('attribute_id = ?', $attributeId)  
          ->where('store_id = ?', $storeId);
        
        if (count($optionIds) > 0){
          $values = '';
          foreach ($optionIds as $oId)
            $values .= ($values != '' ? ' OR ' : '') .  "`value`={$oId}";
            
          $select->where($values);
        }
        
        return (array) $this->_getReadAdapter()->fetchCol($select);    
    }
    

}
