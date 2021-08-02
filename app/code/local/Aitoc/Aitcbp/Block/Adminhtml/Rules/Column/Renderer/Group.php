<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Column_Renderer_Group extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
//	protected $_defaultWidth = 200;
	const SHOW_GROUP_LIMIT = 3;
	
	public function render(Varien_Object $row)
    {
    	if ($data = $row->getData($this->getColumn()->getIndex())) {
    		$ruleId = $row->getData('entity_id');
    		$collection = Mage::getModel('aitcbp/group')->getCollection();
    		/* @var $collection Aitoc_Aitcbp_Model_Mysql4_Group_Collection */
    		$groups = explode(',', $data);
    		$collection->addFieldToFilter('entity_id', array('in'=>$groups));
    		$groups = $collection->toOptionArray();
    		
    		$overLimit = false;
    		if (sizeof($groups) > self::SHOW_GROUP_LIMIT) {
    			$groups = array_slice($groups, 0, self::SHOW_GROUP_LIMIT);
    			$overLimit = true;
    		}
    		$html = '';
    		foreach ($groups as $group) {
    			$html .= $group['label'];
    			$html .= '<br />';
    		}
    		if ($overLimit) $html .= '...';
    		
    		
    		return $html;
    	}
    	return $this->getColumn()->getDefault();
    }
}
?>