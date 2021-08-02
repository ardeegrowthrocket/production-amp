<?php
class Growthrocket_Gtm_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Use for google tracking
     * @return string
     */
    public function getDefaultBrand()
    {
        $storeCode = Mage::app()->getStore()->getCode();
        $brandArray = array (
            'amp' => 'Allmoparparts',
            'default' => 'Allmoparparts',
            'lfp' => 'LevittownFordParts',
            'jau' => 'JeepsAreUs',
            'jau_en' => 'JeepsAreUs',
            'spp_en' => 'SubaruPartsPros',
            'spp' => 'SubaruPartsPros',
            'rau' => 'RamsAreUs',
            'rau_en' => 'RamsAreUs',
            'mopar' => 'MoparGenuinePart',
            'mgp' => 'MoparGenuinePart',
            'sop' => 'SubaruOnlineParts',
            'mop' => 'MoparOnlineParts',
            'mogp' => 'MoparOriginalParts'
        );

        if(isset($brandArray[$storeCode])) {
            return $brandArray[$storeCode];
        }
    }

    /**
     * @param $product
     * @return string
     */
    public function getCustomCategory($product)
    {
        $categories  = $product->getAttributeText('auto_type');
        if(is_array($categories)) {
          return  $categories;
        }else {
            return $categories;
        }
    }

    /**
     * @return string
     */
    public function getListType()
    {
        $routeName = Mage::app()->getRequest()->getRouteName();
        switch($routeName){
            case 'catalogsearch':
                $list = "Search Result Page";
                break;
            default:
                $list = "Category Page";
        }

        return  $list;
    }
}
	 