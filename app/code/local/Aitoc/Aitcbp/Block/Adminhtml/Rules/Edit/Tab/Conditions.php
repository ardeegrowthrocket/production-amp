<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form 
{
	public function _prepareLayout() {
		$this->setTemplate('aitcbp/rule/conditions.phtml');
		return parent::_prepareLayout();
	}
	
	public function getRule() {
		$rule = Mage::registry('aitacbp_rules_data');
		return $rule;
	}
	
	public function getConditions() {
		$rule = $this->getRule();
		if ($rule->getConditions()) {
			return unserialize($rule->getCoditions());
		}
		return array(
			'revenue_number' => Mage::app()->getRequest()->getParam('revenue_number', '7'),
			'revenue_period' => Mage::app()->getRequest()->getParam('revenue_period', 'day'),
			'revenue_clause' => Mage::app()->getRequest()->getParam('revenue_clause', 'day'),
		);
	}
	
	public function getNumbers() {
		$numbers = array();
		for ($i=1; $i<=12; $i++) $numbers[$i] = $i;
		return $numbers;
	}
	
	public function getPeriods() {
		return array(
			'day' => Mage::helper('aitcbp')->__('day (s)'),
			'month' => Mage::helper('aitcbp')->__('month (s)'),
			'year' => Mage::helper('aitcbp')->__('year (s)'),
		);
	}
	
	public function getClauses() {
		return array(
			'==' => Mage::helper('aitcbp')->__('is'),
			'<>' => Mage::helper('aitcbp')->__('is not'),
			'>=' => Mage::helper('aitcbp')->__('equals or greater than'),
			'<=' => Mage::helper('aitcbp')->__('equals or less than'),
			'>' => Mage::helper('aitcbp')->__('greater than'),
			'<' => Mage::helper('aitcbp')->__('less than'),
//			'in' => Mage::helper('aitcbp')->__('is one of'),
//			'nin' => Mage::helper('aitcbp')->__('is not one of'),
		);
	}
}
?>