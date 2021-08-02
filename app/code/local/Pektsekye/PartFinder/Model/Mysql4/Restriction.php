<?php

class Pektsekye_PartFinder_Model_Mysql4_Restriction extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('partfinder/restriction', 'selector_row_id');
    }  




    public function getProductIds($rowId)
    {
      $pIds = $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
      		->from($this->getMainTable(), 'product_id')
      		->where('selector_row_id =?', $rowId)
      );
      
      $upIds = $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
      		->from($this->getTable('partfinder/universal_product'), 'product_id')
      ); 
           
      return array_merge($pIds, $upIds);
    }



    public function insertIds($productIdsByRowId)
    { 			
			$valuesStr = '';
			foreach ($productIdsByRowId as $rowId => $productIds){
			  foreach($productIds as $productId)
				  $valuesStr .= ($valuesStr != '' ? ',' : '') . "({$rowId},{$productId})";	  
			}
				      	            								
      $this->_getWriteAdapter()->raw_query("
          INSERT INTO `{$this->getMainTable()}` (
            `selector_row_id`,            
            `product_id`             
          ) VALUES {$valuesStr}
      ");      
    }



    public function insertProduct($rowIds, $productId)
    {         
      $valuesStr = '';
      foreach ($rowIds as $rowId)
        $valuesStr .= ($valuesStr != '' ? ',' : '') . "({$rowId},{$productId})";	  
            
      $this->_getWriteAdapter()->raw_query("
          INSERT INTO `{$this->getMainTable()}` (
            `selector_row_id`,            
            `product_id`             
          ) VALUES {$valuesStr}
      ");
            
    }



    public function insertUniversalProduct($productId)
    {         	              
      $this->_getWriteAdapter()->insert($this->getTable('partfinder/universal_product'), array('product_id' => $productId));   
    }



    public function getAllMagentoProductIds()
    { 	
      return $this->_getReadAdapter()->fetchPairs("SELECT `sku`,`entity_id` FROM {$this->getTable('catalog/product')}");
    }
    
        
    
    public function updateTextRestrictions($entityTypeId, $attributeId, $data)
    { 
      $productIdsStr = implode(',', array_keys($data));  
      
      $this->_getWriteAdapter()->raw_query("DELETE FROM `{$this->_resources->getTableName('catalog_product_entity_text')}` WHERE attribute_id = {$attributeId} AND `entity_id` IN ({$productIdsStr})");		    	
    
      $values = '';
      foreach ($data as $productId => $restriction){
        $restriction = trim($restriction);          
        if ($restriction != '')
          $values .= ($values != '' ? ',' : '') . "($entityTypeId, $attributeId, 0, {$productId}, {$this->_getWriteAdapter()->quote($restriction)})";        
      }
      
      if ($values != ''){        	  	        	  	  	
        $this->_getWriteAdapter()->raw_query("
          INSERT INTO `{$this->_resources->getTableName('catalog_product_entity_text')}`
           (`entity_type_id` ,
            `attribute_id` ,
            `store_id` ,
            `entity_id` ,
            `value`
            ) VALUES {$values}
        ");
      }
    }      
       
       

    public function getRestrictionData($attributeId)
    {
      return $this->_getReadAdapter()->query("
        SELECT p.sku as product_sku, pet.value
        FROM `{$this->getTable('catalog/product')}` p
        LEFT JOIN `{$this->_resources->getTableName('catalog_product_entity_text')}` pet
          ON pet.entity_id = p.entity_id AND pet.store_id = 0 AND pet.attribute_id = {$attributeId}
      ");
    } 

       
    public function emptyTable()
    {    
			$this->_getWriteAdapter()->truncate($this->getMainTable());
      $this->_getWriteAdapter()->truncate($this->getTable('partfinder/universal_product'));			
		}


    public function deleteProductData($productId)
    {    
      $this->_getWriteAdapter()->delete($this->getMainTable(), "product_id = {$productId}");
      $this->_getWriteAdapter()->delete($this->getTable('partfinder/universal_product'), "product_id = {$productId}");                         
		}
    
}
