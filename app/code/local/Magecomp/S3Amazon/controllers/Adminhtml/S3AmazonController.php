<?php
require_once(Mage::getBaseDir('lib').'/AWS3/autoload.php');
use Aws\S3\S3Client;

class Magecomp_S3Amazon_Adminhtml_S3amazonController extends Mage_Adminhtml_Controller_Action
{
    public function checkAction()
    {
        $accessKey = $this->getRequest()->getParam('accesskey');
        $secretKey = $this->getRequest()->getParam('secretkey');
        $bucket = $this->getRequest()->getParam('new_bucket');
        $region = $this->getRequest()->getParam('region');
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
            $result = 1;
        } catch(Exception $e) {
            $result = 0;
        }
        Mage::app()->getResponse()->setBody($result);
    }

    public function downloadAction()
    {
        $url = $this->getRequest()->getParam("url");
        $config = Mage::helper('s3amazon/config');
        $s3 = Mage::helper('s3amazon/S3');
        if ($s3->isRelevantUrl($url)) {
            $protectedUrl = $s3->generateSecureUrl($url);
            if ($protectedUrl !== false) {
                $this->getResponse()
                    ->setHttpResponseCode(307)
                    ->setHeader("Location", $protectedUrl);
                $this->getResponse()->clearBody();
                $this->getResponse()->sendHeaders();
                return;
            }
        }
    }

    protected function _isAllowed()
    {        
         return Mage::getSingleton('admin/session')->isAllowed('s3amazon');              
    }

}
