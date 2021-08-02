<?php
class Aitoc_Aitcbp_Model_Price_Group extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	protected function _construct()
    {
        $this->_init('aitcbp/price_group');
    }
    
    public function getAllOptions()
    {
    	/*$websiteId = 0;
    	$storeId = Mage::app()->getRequest()->getParam('store');
    	if ($storeId) $websiteId = Mage::helper('aitcbp')->getWebsiteIdByStoreId($storeId);*/
    	
    	$collection = Mage::getModel('aitcbp/group')->getCollection(); //->addWebsiteFilter($storeId);
    	/* @var $collection Aitoc_Aitcbp_Model_Mysql4_Group_Collection */
    	$collection->load();
    	$values = array();
    	$values[] = array(
    		'value' => '0',
    		'label' => Mage::helper('aitcbp')->__('Disabled')
    	);
    	foreach ($collection as $item) {
    		$values[] = array(
    			'value' => $item->getId(),
    			'label' => $item->getGroupName() . ( ($item->getIsActive()) ? '' : ' (' .  Mage::helper('aitcbp')->__('inactive') . ')' )
    		);
    	}
    	return $values;
    	
//    	return array_merge( array(array('value'=>'0', 'label'=>Mage::helper('aitcbp')->__('Disabled'))), $collection->load()->toOptionArray());
    }
}
?>