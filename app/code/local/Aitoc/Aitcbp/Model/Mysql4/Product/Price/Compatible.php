<?php

if(Aitoc_Aitsys_Abstract_Service::get()->isMagentoVersion('>=1.6'))        
{
    class Aitoc_Aitcbp_Model_Mysql4_Product_Price_Compatible extends Mage_Catalog_Model_Resource_Product_Indexer_Abstract
    {
        public function _construct()
        {
            $this->_init('aitcbp/product_price_index', 'product_price_index_id');
            $this->addUniqueField(array(
                'field' => array('product_id', 'website_id')
            ));
            
        }
        
        protected function _copyIdxDataToMainTable($entityIds = null)
        {
            $write = $this->_getWriteAdapter();
            $write->beginTransaction();
            try {
                if ($entityIds)
                {
                    // remove old index
                    $where = $write->quoteInto('product_id IN(?)', $entityIds);
                    $write->delete($this->getMainTable(), $where);
    
                    // remove additional data from index
                    $where = $write->quoteInto('entity_id NOT IN(?)', $entityIds);
                    $write->delete($this->getIdxTable(), $where);
                } else {
                    // remove old index 
                    $write->delete($this->getMainTable());
                }
                // insert new index
    
                $write->query('INSERT INTO '.$this->getMainTable().' SELECT NULL as `product_price_index_id`, `i`.* FROM ' . $this->getIdxTable() . ' as `i`');
                
                $this->commit();
                
            } catch (Exception $e) {
                $this->rollBack();
                throw $e;
            }
    
            return $this;
        }
    }
    
} else {
    class Aitoc_Aitcbp_Model_Mysql4_Product_Price_Compatible extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Abstract
    {
        public function _construct()
        {
            $this->_init('aitcbp/product_price_index', 'product_price_index_id');
            $this->addUniqueField(array(
                'field' => array('product_id', 'website_id')
            ));
            
        }
        
        protected function _copyIdxDataToMainTable($entityIds = null)
        {
            $write = $this->_getWriteAdapter();
            $write->beginTransaction();
            try {
                if ($entityIds)
                {
                    // remove old index
                    $where = $write->quoteInto('product_id IN(?)', $entityIds);
                    $write->delete($this->getMainTable(), $where);
    
                    // remove additional data from index
                    $where = $write->quoteInto('entity_id NOT IN(?)', $entityIds);
                    $write->delete($this->getIdxTable(), $where);
                } else {
                    // remove old index 
                    $write->delete($this->getMainTable());
                }
                // insert new index
                $table  = $this->getMainTable();
                $select = 'SELECT NULL as `product_price_index_id`, `i`.* FROM ' . $this->getIdxTable() . ' as `i`';
                $this->insertFromSelect($select, $table, array(
                    'product_price_index_id', 'product_id', 'website_id', 'group_id', 'price'    
                ));
    
                $this->commit();
            } catch (Exception $e) {
                $this->rollBack();
                throw $e;
            }
    
            return $this;
        }
    }
}
