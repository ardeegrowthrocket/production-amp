<?php

class Aitoc_Aitcbp_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XML_CONFIG_IS_ENABLED = 'catalog/aitcbp/enabled';
	
	protected $_fixedMargin = null;
	protected $_percentMargin = null;
	protected $_customerGroupId = null;
	
	protected $_actions = array();
	protected $_groups = array();
	
	const MARGIN_TYPE_FIXED = 1;
	const MARGIN_TYPE_PERCENT = 2;
	
	const RULE_ACTION_INCREASE = 1;
	const RULE_ACTION_DECREASE = 2;
	
	const ACTION_INCREASE_PERCENT = 1;
	const ACTION_DECREASE_PERCENT = 2;
	const ACTION_INCREASE_FIXED = 3;
	const ACTION_DECREASE_FIXED = 4;
	
	public function getCustomerGroupId() {
		if (is_null($this->_customerGroupId)) {
			$groupId = Mage::getSingleton('customer/session')->getCustomer()->getGroupId();
			if (!$groupId) $groupId = 0;
			$this->_customerGroupId = $groupId;
		}
		return $this->_customerGroupId;
	}
	
	public function getWebsiteIdByStoreId($id) {
		return Mage::app()->getStore($id)->getWebsiteId();
	}
	
	public function isEnabled() {
		if (Mage::getStoreConfig(self::XML_CONFIG_IS_ENABLED)) return true;
		return false;
	}

	public function applyRules($group, $price) {
		$actions = $this->getRuleActions($group, $price);
		foreach ($actions as $action) {
			$amount = $action['increase_decrease'];
			switch ($action['action_value']) {
				case self::ACTION_INCREASE_PERCENT:
					$price = $price * (100 + $amount) / 100;
					break;
				case self::ACTION_DECREASE_PERCENT:
					$price = $price * (100 - $amount) / 100;
					break;
				case self::ACTION_INCREASE_FIXED:
					$price += $amount;
					break;
				case self::ACTION_DECREASE_FIXED:
					$price -= $amount;
					break;
			}
		}
		return $price;
	}
	
	public function getGroup($groupId) {
		if (!isset($this->_groups[$groupId])) {
			$group = Mage::getModel('aitcbp/group')->load($groupId);
			if ($group->getId()) {
				$group->setAmount($this->applyNewRules($group));
			}
			
			$this->_groups[$groupId] = $group;
		}
		return $this->_groups[$groupId];
	}
	
	public function getRuleActions($group) {
		if (!isset($this->_actions[$group->getId()])) {
			$actions = array();
			$rules = Mage::getModel('aitcbp/rule')->getCollection();
			/* @var $rules Aitoc_Aitcbp_Model_Mysql4_Rule_Collection */
			$rules
//				->addFilter('group_id', $group->getId()) // changed for FIND_IN_SET
				->addFilter('is_active', 1)
				->addOrder('priority')
				;
			//where('FIND_IN_SET(?, website_ids) > 0 OR website_ids = 0', $websiteId);
			$rules->getSelect()->where('FIND_IN_SET(?, in_groups) > 0', $group->getId());
			foreach ($rules as $rule) {
				/* @var $rule Aitoc_Aitcbp_Model_Rule */
				$isWorking = false;
				$rule->load($rule->getId());
				$avgSale = Mage::helper('aitcbp')->getAvgSale($rule);

				if ( (false !== $avgSale) and (false !== $rule->getRevenueSum()) )
                {
                	$evalConditions = '$isWorking = doubleval(' . $avgSale . ') ' . $rule->getRevenueClause() . ' doubleval(' . $rule->getRevenueSum() . ');';
                    eval($evalConditions);
                }
                if ($isWorking) {
                	$actions[] = unserialize($rule->getActions());
                }
                
                if ($rule->getFutherRules()) break;
			}
			$this->_actions[$group->getId()] = $actions;
		}
		return $this->_actions[$group->getId()];
	}
	
	public function applyNewRules($group) {
		$actions = $this->getRuleActions($group);
		$resultingAmount = $group->getAmount();
		foreach ($actions as $action) {
			$amount = $action['increase_decrease'];
			switch ($action['action_value']) {
				case self::RULE_ACTION_INCREASE:
					$resultingAmount += $amount;
					break;
				case self::RULE_ACTION_DECREASE:
					$resultingAmount -= $amount;
					break;
			}
		}
		return $resultingAmount;
	}
	
	public function getPrice(Mage_Catalog_Model_Product $product, Aitoc_Aitcbp_Model_Group $group) {

		$price = $product->getPrice();
		$cost = $product->getCost();
		$type = $group->getCbpType();
		$amount = $group->getAmount();
		
//		$amount = $this->applyNewRules($group);
		if (!$cost) return $price;
        
		switch ($type) {
			
			case self::MARGIN_TYPE_FIXED:
				$price = $cost + $amount;
				break;
				
			case self::MARGIN_TYPE_PERCENT:
				$price = $cost * (100 + $amount) / 100;
				break;
		}
		
//		$price = $this->applyRules($group, $price); // old logic
		
		return max(0, $price);
	}
	
	public function getNewPrice($product) {
		
		$price = $product->getPrice();
		
		if (!$product->getCbpEnabled()) return $price;
		
		$cost = $product->getCost();
		if (!$cost) return $price;
		
		$type = $product->getCbpType();
		if (!$type) return $price;

		switch ($type) {
			
			// fixed
			case 1:
				$price = $cost + $this->getFixedMargin();
				break;
				
			// percent
			case 2:
				$price = $cost * (100 + $this->getPercentMargin()) / 100;
				break;
		}
		
		return $price;
	}

	/** Gets average sales for rule period
	 * 
	 * @param Aitoc_Aitcbp_Model_Rule $rule
	 * @return numeric average sale
	 */
	public function getAvgSale(Aitoc_Aitcbp_Model_Rule $rule)
	{
        $sales       = Mage::getModel('sales/order')->getCollection();
        $fromDate    = strtotime('-'.$rule->getRevenueNumber().' '.$rule->getRevenuePeriod());
        $salesSelect = $sales->getSelect();
        $salesSelect->reset('columns')
            ->columns(
                array('avgsale'   => 'SUM( COALESCE(base_total_paid, 0) ) - SUM( COALESCE(base_total_refunded, 0) )',))
            ->where('updated_at > ?', date('Y-m-d H:i:s', $fromDate));
        $avgsaleResult = $sales->getResource()->getReadConnection()->query($salesSelect->__toString());
        /* @var $avgsaleResult Zend_Db_Statement_Pdo */
        $avgSale = $avgsaleResult->fetchColumn(0);

        if ( (false === $avgSale) || !$avgSale)
        {
            $avgSale = 0;
        }

        return $avgSale;
	}
}