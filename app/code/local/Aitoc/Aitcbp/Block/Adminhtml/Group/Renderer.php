<?php
class Aitoc_Aitcbp_Block_Adminhtml_Group_Renderer extends Varien_Data_Form_Element_Select 
{
	public function getElementHtml()
	{
		$html = parent::getElementHtml();
		$id = 'show_price_'.$this->getHtmlId();
		return $html . '<div id="' . $id . '" style="display: none;"></div>';
	}
}
?>