<?php

class Growthrocket_Slider_Block_Slider extends Mage_Core_Block_Template
{

    protected $_collection;

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->getCollection();
        return parent::_prepareLayout();
    }

    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getCollection()
    {
        if(!$this->_collection) {
            $storeId = $store = Mage::app()->getStore()->getId();

            $collection = Mage::getModel("slider/grslider")->getCollection();
            $collection->addFieldToSelect('*');
            $collection->addFieldToFilter('is_active', 1);
            $collection->addFieldToFilter('store_ids', array(
                ['finset' => array(0)],
                ['finset' => array($storeId)],
            ));
            $collection->setOrder('position','ASC');
            $record = array();
            foreach ($collection as $key => $item){
                $record[$key] = $item->getData();
            }

            $this->_collection = $record;
        }

        return $this->_collection;
    }

}