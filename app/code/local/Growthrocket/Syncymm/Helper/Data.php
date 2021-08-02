<?php
class Growthrocket_Syncymm_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isEnableSync()
    {
        return Mage::getStoreConfig('gr_dd_sync/gr_sync_group/enable', Mage::app()->getStore());
    }

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getAuthUsername()
    {
        return Mage::getStoreConfig('gr_dd_sync/gr_sync_group/auth_username', Mage::app()->getStore());
    }

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getAuthPassword()
    {
        return Mage::getStoreConfig('gr_dd_sync/gr_sync_group/auth_password', Mage::app()->getStore());
    }

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getSyncUrl()
    {
        return Mage::getStoreConfig('gr_dd_sync/gr_sync_group/auth_url', Mage::app()->getStore());
    }


    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getSyncLimit()
    {
        return Mage::getStoreConfig('gr_dd_sync/gr_sync_group/sync_limit', Mage::app()->getStore());
    }

}