<?php
class Aitoc_Aitcbp_Model_Mysql4_Rule extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
    {
        $this->_init('aitcbp/rule', 'entity_id');
    }
    
    protected function _afterLoad(Mage_Core_Model_Abstract $object) {
    	if ($object->getConditions()) {
    		$conditions = unserialize($object->getConditions());
    		$object->setRevenueNumber($conditions['revenue_number']);
    		$object->setRevenuePeriod($conditions['revenue_period']);
    		$object->setRevenueClause($conditions['revenue_clause']);
    		$object->setRevenueSum($conditions['revenue_sum']);
    	}
    	if ($object->getActions()) {
    		$actions = unserialize($object->getActions());
    		$object->setActionValue($actions['action_value']);
    		$object->setIncreaseDecrease($actions['increase_decrease']);
    		$object->setFutherRules($actions['futher_rules']);
    	}
    	parent::_afterLoad($object);
    }
    
    protected function _beforeSave(Mage_Core_Model_Abstract $object) {
    	if (is_array($object->getInGroups())) $object->setInGroups(implode(',', $object->getInGroups()));
    	$object->setConditions(serialize(
    		array(
    			'revenue_number' => $object->getRevenueNumber(),
    			'revenue_period' => $object->getRevenuePeriod(),
    			'revenue_clause' => $object->getRevenueClause(),
    			'revenue_sum' => $object->getRevenueSum(),
    		)
    	));
    	$object->setActions(serialize(
    		array(
    			'action_value' => $object->getActionValue(),
    			'increase_decrease' => $object->getIncreaseDecrease(),
    			'futher_rules' => $object->getFutherRules(),
    		)
    	));
    	return parent::_beforeSave($object);
    }
    
    protected function _afterSave(Mage_Core_Model_Abstract $object) 
    {   
        $groupIds = explode(',', $object->getInGroups());
        $productIds = array();
        $excludeIds = array();
        foreach ($groupIds as $groupId)
        {
            $group = Mage::helper('aitcbp')->getGroup($groupId);
            $productIds = array_merge($productIds, $group->getAssociatedProducts());
        }
        $collection = Mage::getModel('aitcbp/group')->getCollection()
            ->addFieldToFilter('entity_id', array('nin' => $groupIds));
        foreach ($collection as $item)
        { 
            $excludeIds = array_merge($excludeIds, $item->getAssociatedProducts());
        }
        if (count($productIds))
        {
            Mage::getModel('aitcbp/product_price_index')->reindexEntity($productIds); 

            Mage::getResourceSingleton('catalogrule/rule')
                ->applyAllRulesForDateRange();

            $event = Mage::getSingleton('index/event')
                ->setNewData(array('reindex_price_product_ids' => $productIds));
            Mage::getResourceSingleton('catalog/product_indexer_price')
                ->catalogProductMassAction($event);
        }
        if (count($excludeIds))
        {
            Mage::getModel('aitcbp/product_price_index')->reindexEntity($excludeIds); 

            Mage::getResourceSingleton('catalogrule/rule')
                ->applyAllRulesForDateRange();
                    
            $event = Mage::getSingleton('index/event')
                ->setNewData(array('reindex_price_product_ids' => $excludeIds));
            Mage::getResourceSingleton('catalog/product_indexer_price')
                ->catalogProductMassAction($event);    
        }
        return parent::_afterSave($object);
    }
}