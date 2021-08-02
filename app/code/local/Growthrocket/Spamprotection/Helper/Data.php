<?php
class Growthrocket_Spamprotection_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return mixed
     */
    public function getUserIP()
    {
        if(!empty($_SERVER['REMOTE_ADDR'])){
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ip;
    }

    /**
     * @return mixed
     */
    public function isEnable()
    {
        return Mage::getStoreConfig('gr_spam_section/gr_spam_section_group/enable');
    }

    /**
     * @return mixed
     */
    public function getThresholdTime()
    {
        return Mage::getStoreConfig('gr_spam_section/gr_spam_section_group/threshold_time');
    }

    /**
     * @return mixed
     */
    public function getThresholdRequest()
    {
        return Mage::getStoreConfig('gr_spam_section/gr_spam_section_group/threshold_request');
    }

    /**
     * @return array
     */
    public function getWhitelistedIp()
    {
        $whiteListedIps = array();
        $recordIps =  Mage::getStoreConfig('gr_spam_section/gr_spam_section_group/whitelist_ip');
        if(!empty($recordIps)){
            $whiteListedIps = explode(',', $recordIps);
        }

        return $whiteListedIps;
    }

    /**
     * @return array
     */
    public function getBlockedIp()
    {
        $blockedIps = array();
        $recordIps =  Mage::getStoreConfig('gr_spam_section/gr_spam_section_group/blocked_ip');
        if(!empty($recordIps)){
            $blockedIps = explode(',', $recordIps);
        }

        return $blockedIps;
    }

    /**
     * @return int
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    /**
     * @param $USER_AGENT
     * @return bool
     */
    public function isCrawlerDetect($USER_AGENT)
    {
        $crawlers = array(
            array('Google', 'Google'),
            array('Bing', 'Bing'),
            array('Facebook', 'Facebook'),
        );

        foreach ($crawlers as $c)
        {
            if (stristr($USER_AGENT, $c[0]))
            {
                return true;
            }
        }

        return false;
    }

    public function allowedUserAgent()
    {
        return $this->isCrawlerDetect($_SERVER['HTTP_USER_AGENT']);
    }
}
	 