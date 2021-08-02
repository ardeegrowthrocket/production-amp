<?php
require_once "Mage/Downloadable/controllers/DownloadController.php";
class Magecomp_S3Amazon_DownloadController extends Mage_Downloadable_DownloadController
{
    protected function _processDownload($url, $resourceType)
    {
        $this->_log("Entering _processDownload()");
        
        $config = Mage::helper('s3amazon/config');
        $s3     = Mage::helper('s3amazon/S3');

        if ($config->isConfigured()) {
            if ($resourceType == Mage_Downloadable_Helper_Download::LINK_TYPE_URL) {
                if ($s3->isRelevantUrl($url)) {
                    $protected_url = $s3->generateSecureUrl($url);
                    if ($protected_url !== false) {
                        $this->_log("Generated protected URL for $url: $protected_url");
                        $this->getResponse()
                            // Temporary redirect to avoid caching
                            ->setHttpResponseCode(307)
                            ->setHeader("Location", $protected_url);
                        $this->getResponse()->clearBody();
                        $this->getResponse()->sendHeaders();
                        return;
                    } else {
                        $this->_log("Unable to generate protected URL from $url");
                    }
                } else {
                    $this->_log("Not intercepting the $url, as it's not an S3 URL");
                }
            } else {
                $this->_log("Not intercepting the $url to download as it's not a remote file!");
            }
        } else {
            $this->_log("Not intercepting the $url download as we're either not enabled, or we're not fully configured");
        }
        return parent::_processDownload($url, $resourceType);
    }

    protected function _log($message, $level = Zend_Log::DEBUG) {
        Mage::helper('s3amazon')->log($message, $level);
    }
}
