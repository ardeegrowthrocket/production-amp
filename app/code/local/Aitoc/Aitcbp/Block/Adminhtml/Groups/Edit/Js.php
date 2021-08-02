<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups_Edit_Js extends Mage_Core_Block_Template 
{
	
	public function getProductsJson()
    {
    	return '{}';
    	$collection = Mage::registry('current_cbp_group');
    	$out = '';
    	foreach ($collection as $product) {
    		$out .= $product->getId();
    	}
    	return $out;
        $products = $this->getGroup()->getAssociatedProducts();
        
        if (!empty($products)) {
            return Mage::helper('core')->jsonEncode($products);
        }
        return '{}';
    }
    
    public function getGroup()
    {
    	return Mage::registry('aitacbp_groups_data');
    }
}
?>