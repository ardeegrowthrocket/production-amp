<?php
class Growthrocket_Faq_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @return array|mixed
     * @throws Zend_Cache_Exception
     */
    public function getPartNames()
    {
        $partName = array('' => 'Please Select');
        $cacheId = 'part_names_cache';
        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $partName = unserialize($data_to_be_cached);

        } else {

            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addAttributetoSelect('part_name');
            $collection->groupByAttribute('part_name');

            foreach ($collection as $product){
                if(!empty($product->getPartName())){
                    $partName[$product->getPartName()] =  $product->getPartName();
                }
            }
            Mage::app()->getCache()->save(serialize($partName), $cacheId, array(), (60 * 60) * 24 );
        }
        ksort($partName);
        return $partName;
    }
}
	 