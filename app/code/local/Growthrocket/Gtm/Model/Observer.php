<?php
class Growthrocket_Gtm_Model_Observer
{

    public function addCustomGaTracking(Varien_Event_Observer $observer)
    {
        $item = $observer->getItem();
        $item->setLastAdded(true);

        if ($item->getLastAdded()) {
            Mage::getSingleton('core/session')->setCartRecentAdded($item->getId());
        }

    }
		
}

