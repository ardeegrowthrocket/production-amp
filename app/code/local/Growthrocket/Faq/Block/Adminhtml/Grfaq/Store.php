<?php

class Growthrocket_Faq_Block_Adminhtml_Grfaq_Store  extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row)
    {
        $websiteName = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $websiteName[$store->getId()] = $website->getName() . " ({$store->getName()})";
                }
            }
        }

        $view = array();
        $value =  $row->getData($this->getColumn()->getIndex());
        if(!empty($value)){
            $storeIds = explode(',', $value);
            foreach ($storeIds as $storeId){
                $view[] =   $websiteName[$storeId];
            }
        }
        return implode('<br>', $view);
    }

}
