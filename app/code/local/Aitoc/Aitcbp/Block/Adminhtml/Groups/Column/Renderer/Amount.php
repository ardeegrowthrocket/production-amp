<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups_Column_Renderer_Amount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Price
{
	protected $_defaultWidth = 100;
	
	public function render(Varien_Object $row)
    {
    	switch ($row->getCbpType())
    	{
    		// fixed
    		case '1':
		        if ($data = $row->getData($this->getColumn()->getIndex())) {
		            $currency_code = $this->_getCurrencyCode($row);
		
		            if (!$currency_code) {
		                return $data;
		            }
		
		            $data = floatval($data) * $this->_getRate($row);
		            $data = sprintf("%f", $data);
		            $data = Mage::app()->getLocale()->currency($currency_code)->toCurrency($data);
		            return $data;
		        }
	        	break;
	        	
	        // percent
    		case '2':
    			if ($data = $row->getData($this->getColumn()->getIndex())) {
	    			$data = floatval($data);
	    			$data = sprintf("%2.2f%%", $data);
	    			return $data;
    			}
    			break;
    			
    		default:
    			return $this->getColumn()->getDefault();
    			break;
    	}
        return $this->getColumn()->getDefault();
    }
}
?>