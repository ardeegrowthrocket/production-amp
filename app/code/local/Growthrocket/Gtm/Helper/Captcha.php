<?php
class Growthrocket_Gtm_Helper_Captcha extends Mage_Core_Helper_Abstract
{

    /**
     * Check if Captcha is Enabled
     * @return bool
     */
    public function isEnabled()
    {
      return   (bool) Mage::getStoreConfig('google_captcha/gc_groups/enable');
    }

    /**
     * Get site Key
     * @return mixed
     */
    public function getSiteKey()
    {
        return   Mage::getStoreConfig('google_captcha/gc_groups/sitekey');
    }

    /**
     * get Secret Key
     * @return mixed
     */
    public function getSecretKey()
    {
        return   Mage::getStoreConfig('google_captcha/gc_groups/secretkey');
    }


    /**
     * get Score Threshold
     * @return mixed
     */
    public function getScoreThreshold()
    {
        return   Mage::getStoreConfig('google_captcha/gc_groups/threshold');
    }

    /**
     * Validate if captcha is enable and category is selected
     * @param string $category
     * @return bool
     */
    public function isEnableCaptchaCategory($category = '')
    {
        $isEnable = $this->isEnabled();
        $isInCategory = false;
        $availableCategories = explode(',', Mage::getStoreConfig('google_captcha/gc_groups/recaptcha_category'));
        if(in_array($category, $availableCategories)) {
            $isInCategory = true;
        }

        return $isEnable && $isInCategory;
    }

    /**
     * Validate token on google server
     * @param $token
     * @return string
     */
    public function verifyCaptcha($token)
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $params = array(
            'secret' => $this->getSecretKey(),
            'response' => $token
        );

        $connection = new Varien_Http_Adapter_Curl();
        $connection->connect($url);
        $connection->setConfig(array('timeout' => 15, 'header' => false));
        $connection->write(Zend_Http_Client::POST, $url, '1.1', array(), $params);

        $response = $connection->read();
        $connection->close();

        return $response;
    }
}