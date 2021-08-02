<?php
class Growthrocket_Gtm_Helper_Review extends Mage_Core_Helper_Abstract
{

    /**
     * Check if Captcha is Enabled
     * @return bool
     */
    public function isEnabled()
    {
        return   (bool) Mage::getStoreConfig('google_gc_review/gc_groups/enable');
    }

    /**
     * Get site Key
     * @return mixed
     */
    public function getMerchantId()
    {
        return   Mage::getStoreConfig('google_gc_review/gc_groups/merchant_id');
    }

    /**
     * get Secret Key
     * @return mixed
     */
    public function getPosition()
    {
        return   Mage::getStoreConfig('google_gc_review/gc_groups/position');
    }

    /**
     * @return mixed
     */
    public function getConfirmationPosition()
    {
        return   Mage::getStoreConfig('google_gc_review/gc_groups/checkout_position');
    }

    /**
     * @return string
     * @throws Zend_Date_Exception
     */
    public function getEstimatedDate()
    {
        $estimatedDate = !empty(Mage::getStoreConfig('google_gc_review/gc_groups/estimated_delivery_date')) ? Mage::getStoreConfig('google_gc_review/gc_groups/estimated_delivery_date') : 2;

        $date = new Zend_Date(Mage::getModel('core/date')->timestamp());
        $date->addDay($estimatedDate);
        return  $date->toString('YYYY-MM-dd');
    }
}