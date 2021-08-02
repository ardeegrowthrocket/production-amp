<?php class Homebase_Pgrid_Block_Adminhtml_Catalog_Product_Renderer_Autotype extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row)
    {
        $customCategory = $row->getAttributeText('auto_type');

        if(!empty($customCategory)) {
            if(is_array($customCategory)){
                return implode(',<br>',$customCategory);
            }else{
                return $customCategory;
            }
        }else {
            echo $row->getAutoType();
        }
    }
}
