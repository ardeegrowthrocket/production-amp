<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Column_Filter_Group extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select
{
	public function getCondition()
	{
		if (is_null($this->getValue())) {
			return null;
		}
		return array('finset' => $this->getValue());
    }
}
?>