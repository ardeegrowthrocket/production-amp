<?php

/**
 * Fieldset renderer for Sirv config
 *
 */
class MagicToolbox_Sirv_Block_System_Config_Form_Fieldset_Notice extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $comment = $element->getComment()
            ? $element->getComment() . '<br />'
            : '';
        return '<div class="comment">' . $comment . $this->_getModuleVersion() . '</div>';
    }

    /**
     * Return module version info
     *
     * @return string
     */
    protected function _getModuleVersion()
    {
        $currentVersion = (string) Mage::getConfig()->getNode()->modules->MagicToolbox_Sirv->version;
        $versionString = 'Version: ' . $currentVersion;

        $hostname = 'www.magictoolbox.com';
        $errno = 0;
        $errstr = '';
        $path = 'api/platform/sirvmagento/version/?t=' . time();
        $handle = @fsockopen('ssl://' . $hostname, 443, $errno, $errstr, 30);
        if ($handle) {
            $response = '';
            $headers  = "GET /{$path} HTTP/1.1\r\n";
            $headers .= "Host: {$hostname}\r\n";
            $headers .= "Connection: Close\r\n\r\n";
            fwrite($handle, $headers);
            while (!feof($handle)) {
                $response .= fgets($handle);
            }
            fclose($handle);
            $response = substr($response, strpos($response, "\r\n\r\n") + 4);
            if (function_exists('json_decode')) {
                $responseObj = json_decode($response);
            } else {
                $responseObj = Mage::helper('core')->jsonDecode($response);
            }
            if (is_object($responseObj) && isset($responseObj->version)) {
                $match = array();
                if (preg_match('#v([0-9]++(?:\.[0-9]++)*+)#is', $responseObj->version, $match)) {
                    if (version_compare($currentVersion, $match[1], '<')) {
                        $versionString .= '&nbsp;&nbsp;&nbsp;&nbsp;Latest version: '.$match[1].' (<a href="https://sirv.com/integration/magento/" target="_blank" style="margin: 0;">download zip</a>)';
                    }
                }
            }
        }

        return $versionString;
    }
}
