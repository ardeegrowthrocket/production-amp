<?php

class Growthrocket_Customexport_Block_Adminhtml_Customexportbackend extends Mage_Adminhtml_Block_Template
{

    protected $_websites;

    /**
     * @return mixed
     */
    public function getAllStores()
    {
        foreach (Mage::app()->getWebsites() as $website) {

            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    if($store->getName() == 'German'){
                        continue;
                    }
                    $this->_websites[$store->getId()] = array(
                        'code' => $website->getCode(),
                        'name' => $website->getName()
                    );
                }
            }
        }

        return $this->_websites;
    }
}