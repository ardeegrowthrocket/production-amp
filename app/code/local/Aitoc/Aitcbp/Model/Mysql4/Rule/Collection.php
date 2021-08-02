<?php
class Aitoc_Aitcbp_Model_Mysql4_Rule_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
    {
    	parent::_construct();
        $this->_init('aitcbp/rule');
    }
    
    public function addGroups() {
//    	$this->getSelect()->joinLeft(array('group_table'=>$this->getTable('aitcbp/group')), 'main_table.group_id=group_table.entity_id', array('group_name'));
    	return $this;
    }
}
?>