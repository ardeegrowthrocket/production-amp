<?php

class Aitoc_Aitcbp_Model_Mysql4_Rewrite_Product_Indexer_Price extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price
{
    /**
     * Copy relations product index from primary index to temporary index table by parent entity
     *
     * @param array|int $parentIds
     * @package array|int $excludeIds
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price
     */
    protected function _copyRelationIndexData($parentIds, $excludeIds = null)
    {
        $write  = $this->_getWriteAdapter();
        $select = $write->select()
            ->from($this->getTable('catalog/product_relation'), array('child_id'))
            ->where('parent_id IN(?)', $parentIds);
        if (!is_null($excludeIds)) {
            if (is_numeric($excludeIds) || (is_array($excludeIds) && count($excludeIds)))
            {
                $select->where('child_id NOT IN(?)', $excludeIds);    
            }
        }
        
        $children = $write->fetchCol($select);

        if ($children) {
            $select = $write->select()
                ->from($this->getMainTable())
                ->where('entity_id IN(?)', $children);
			if(version_compare(Mage::getVersion(), '1.6.0.0', '>='))
		    {
			    $query  = $select->insertFromSelect($this->getIdxTable(), array(), false);
			}
			else
			{
                $query  = $select->insertFromSelect($this->getIdxTable());
			}
            $write->query($query);
        }

        return $this;
    }
    
    public function aitocCatalogProductMassAction(Mage_Index_Model_Event $event)
	{
        if(!Mage::getStoreConfig('catalog/aitcbp/reindex'))
        {
            return $this->catalogProductMassAction($event);
        }
        $data = $event->getNewData();
        if (empty($data['reindex_price_product_ids'])) {
            return $this;
        }
        
        $processIds = $data['reindex_price_product_ids'];
		if(method_exists($this,'reindexProductIds'))
		{
        $this->reindexProductIds($processIds);
        }
		else
		{
			$this->cloneIndexTable(true);
			$write  = $this->_getWriteAdapter();
			// retrieve products types
			$select = $write->select()
				->from($this->getTable('catalog/product'), array('entity_id', 'type_id'))
				->where('entity_id IN(?)', $processIds);
			$pairs  = $write->fetchPairs($select);
			$byType = array();
			foreach ($pairs as $productId => $productType) {
				$byType[$productType][$productId] = $productId;
			}

			$compositeIds    = array();
			$notCompositeIds = array();

			foreach ($byType as $productType => $entityIds) {
				$indexer = $this->_getIndexer($productType);
				if ($indexer->getIsComposite()) {
					$compositeIds += $entityIds;
				} else {
					$notCompositeIds += $entityIds;
				}
			}

			if (!empty($notCompositeIds)) {
				$select = $write->select()
					->from(
						array('l' => $this->getTable('catalog/product_relation')),
						'parent_id')
					->join(
						array('e' => $this->getTable('catalog/product')),
						'e.entity_id = l.parent_id',
						array('type_id'))
					->where('l.child_id IN(?)', $notCompositeIds);
				$pairs  = $write->fetchPairs($select);
				foreach ($pairs as $productId => $productType) {
					if (!in_array($productId, $processIds)) {
						$processIds[] = $productId;
						$byType[$productType][$productId] = $productId;
						$compositeIds[$productId] = $productId;
					}
				}
			}

			if (!empty($compositeIds)) {
				$this->_copyRelationIndexData($compositeIds, $notCompositeIds);
			}

			$indexers = $this->getTypeIndexers();
			foreach ($indexers as $indexer) {
				if (!empty($byType[$indexer->getTypeId()])) {
					$indexer->reindexEntity($byType[$indexer->getTypeId()]);
				}
			}

			$this->_copyIndexDataToMainTable($processIds);
		}
		return $this;
    }

}

