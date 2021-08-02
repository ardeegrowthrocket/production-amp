<?php
class Aitoc_Aitcbp_Model_System_Config_Backend_Catalog_Aitcbp_Enabled extends Mage_Core_Model_Config_Data
{
    /**
     * After enable price required reindex
     *
     * @return Aitoc_Aitcbp_Model_System_Config_Backend_Catalog_Aitcbp_Enabled
     */
    protected function _afterSave()
    {
        if ($this->isValueChanged()) {
            Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price')
                ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX); 
                
            Mage::getResourceSingleton('catalogrule/rule')->applyAllRulesForDateRange();
        }
    }
}
