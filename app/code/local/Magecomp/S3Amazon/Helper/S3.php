<?php
class Magecomp_S3Amazon_Helper_S3 extends Mage_Core_Helper_Abstract
{
    public function isRelevantUrl($url)
    {
        preg_match_all("/^https?:\/\/.*\.amazonaws\.com\/(.*)$/", $url, $matches);
        return $matches && count($matches) > 0 && count($matches[0]) > 0;
    }

    public function generateSecureUrl($url)
    {
        $secret = Mage::helper('s3amazon/config')->getAmazonSecretKey();
        $expires = $this->_getExpiryTime();

        if ($redirect_url = $this->_tryBucketSubdomain($url, $secret, $expires)) {
            return $redirect_url;
        } else if ($redirect_url = $this->_tryBucketRequestPath($url, $secret, $expires)) {
            return $redirect_url;
        } else {
            return false;
        }
    }

    protected function _tryBucketSubdomain($url, $secret, $expires)
    {
        preg_match_all("/^https?:\/\/(.*)\.s3[\w-]*\.amazonaws\.com\/(.*)$/", $url, $matches);

        if ($matches && count($matches) > 0 && count($matches[0]) > 0) {
            list($full_url, $bucket, $filename) = $matches;

            $string_to_sign = "GET\n\n\n$expires\n/" . $bucket[0] . "/" . $filename[0];

            $this->_log("(Subdomain) Signing string:\n$string_to_sign");

            $parameters =  array(
                "AWSAccessKeyId" => Mage::helper('s3amazon/config')->getAmazonAccessKey(),
                "Expires"        => $expires,
                "Signature"      => $this->__sign($string_to_sign, $secret)
            );

            return $this->__buildUrl($url, $parameters);
        }

        return false;
    }

    protected function _tryBucketRequestPath($url, $secret, $expires)
    {
        preg_match_all("/^https?:\/\/.*\.amazonaws\.com\/(.*)$/", $url, $matches);

        if ($matches && count($matches) > 0 && count($matches[0]) > 0) {
            list($full_url, $filename) = $matches;

            $string_to_sign = "GET\n\n\n$expires\n/" . $filename[0];

            $this->_log("(Request Path) Signing string:\n$string_to_sign");

            $parameters =  array(
                "AWSAccessKeyId" => Mage::helper('s3amazon/config')->getAmazonAccessKey(),
                "Expires"        => $expires,
                "Signature"      => $this->__sign($string_to_sign, $secret)
            );

            return $this->__buildUrl($url, $parameters);
        }

        return false;
    }

    private function __sign($string, $secret)
    {
        return urlencode(base64_encode(hash_hmac('sha1', utf8_encode($string), $secret, true)));
    }

    private function __buildUrl($url, $parameters)
    {
        $parameter_string = "?";
        foreach ($parameters as $key => $value) {
            $parameter_string .= "$key=$value&";
        }

        return $url.$parameter_string;
    }

    protected function _getExpiryTime()
    {
        $expiry = Mage::helper('s3amazon/config')->getAmazonRequestTimeout();
        if ($expiry < 1) {
            $expiry = 1;
        }
        return time() + $expiry;
    }

    protected function _log($message, $level = Zend_Log::DEBUG) {
        Mage::helper('s3amazon')->log($message, $level);
    }
}
