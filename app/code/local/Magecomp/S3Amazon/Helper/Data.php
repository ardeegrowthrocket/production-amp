<?php
require_once(Mage::getBaseDir('lib').'/AWS3/autoload.php');
use Aws\S3\S3Client;
class Magecomp_S3Amazon_Helper_Data extends Mage_Core_Helper_Abstract
{
    public $error = "";

    public function getClient()
    {
        $storeId = Mage::app()->getStore()->getId();
        $accessKey = Mage::getStoreConfig('s3amazon/s3_amazon_options/access_key');
        $secretKey = Mage::getStoreConfig('s3amazon/s3_amazon_options/secret_key');
        $bucket = Mage::getStoreConfig('s3amazon/s3_amazon_options/bucket_key');
        $region = Mage::getStoreConfig('s3amazon/s3_amazon_options/region');
        $this->bucket = $bucket;
        try {
            $client = new S3Client(array(
                        'version'     => 'latest',
                        'credentials' => array(
                            'key'    => $accessKey,
                            'secret' => $secretKey,
                        ),
                        'region'  => $region,
                    ));
            $client->listObjects(array('Bucket' => $bucket));
        } catch(Exception $e) {
            $this->error = $e->getMessage();
            $client = false;
        }
        return $client;
    }

    public function log($message, $level = Zend_Log::DEBUG)
    {
        $config = Mage::helper('s3amazon/config');
        $moduleLogActive = 1;
        Mage::log("[magecomp_S3Amazon] $message", $level, $config->getLogLocation(), $moduleLogActive);
    }
}
