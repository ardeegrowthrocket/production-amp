<?php
class Magecomp_S3Amazon_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_AMAZON_ACCESS     = 's3amazon/s3_amazon_options/access_key';
    const XML_AMAZON_SECRET     = 's3amazon/s3_amazon_options/secret_key';
    const XML_AMAZON_TIMEOUT    = 's3amazon/s3_amazon_options/request_timeout';

    public function isConfigured()
    {
        return $this->getAmazonAccessKey() && $this->getAmazonSecretKey();
    }

    public function getAmazonAccessKey()
    {
        return $this->_getStoreConfig(self::XML_AMAZON_ACCESS);
    }

    public function getAmazonSecretKey()
    {
        return $this->_getStoreConfig(self::XML_AMAZON_SECRET);
    }

    public function getAmazonRequestTimeout()
    {
        return $this->_getStoreConfig(self::XML_AMAZON_TIMEOUT);
    }

    public function getLogLocation()
    {
        return "magecomp_mpdownloadabletoamazon.log";
    }

    protected function _getStoreConfig($xml_path)
    {
        return Mage::getStoreConfig($xml_path, Mage::app()->getStore()->getCode());
    }
}
