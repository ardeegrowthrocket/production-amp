<?php

class Growthrocket_Cmsblog_Model_Cmsblog extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("cmsblog/cmsblog");

    }

    /**
     * @param $identifier
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function loadbyIdenfier($identifier)
    {

        $collection = $this->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('identifier', $identifier)
                ->addFieldToFilter('store_ids', array('finset' => Mage::app()->getStore()->getId()))
                ->getFirstItem();

        return $collection;
    }

}
	 