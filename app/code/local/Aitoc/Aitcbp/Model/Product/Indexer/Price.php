<?php

class Aitoc_Aitcbp_Model_Product_Indexer_Price extends Mage_Catalog_Model_Product_Indexer_Price
{
    public function reindexAll()
    {
        Mage::getModel('aitcbp/product_price_index')->reindexAll(); 
        parent::reindexAll();
        return $this;
    }
    
}

