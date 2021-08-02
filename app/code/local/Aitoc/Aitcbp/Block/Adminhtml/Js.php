<?php
class Aitoc_Aitcbp_Block_Adminhtml_Js extends Mage_Core_Block_Template
{
	
	public function getGroups() 
	{
		/*$websiteId = 0;
    	$storeId = Mage::app()->getRequest()->getParam('store');
    	if ($storeId) $websiteId = Mage::helper('aitcbp')->getWebsiteIdByStoreId($storeId);*/
    	
    	$collection = Mage::getModel('aitcbp/group')->getCollection(); //->addWebsiteFilter($storeId);
    	/* @var $collection Aitoc_Aitcbp_Model_Mysql4_Group_Collection */
    	
    	return $collection;
	}
	
	protected function _getCurrencyCode()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId)->getBaseCurrency()->getCode();
    }
}
?>