<?php

class Aitoc_Aitcbp_Model_Mysql4_Group_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('aitcbp/group');
    }

    protected function _toOptionArray($valueField = 'entity_id', $labelField = 'group_name', $additional = array())
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

    public function getAsOptions()
    {
        $res = array();
        foreach ($this as $item) {
            $res[$item->getId()] = $item->getGroupName();
        }

        return $res;
    }

}
