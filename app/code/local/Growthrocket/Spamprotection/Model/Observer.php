<?php
class Growthrocket_Spamprotection_Model_Observer
{

    public function validateUser(Varien_Event_Observer $observer)
    {
        $_helper = Mage::helper('spamprotection');
        $action = Mage::app()->getRequest()->getActionName();

        $blockIps = $_helper->getBlockedIp();
        if(!empty($blockIps)){
            foreach ($blockIps as $ip){
                if(strpos($_helper->getUserIP(), $ip) !== false){
                    $url = Mage::getBaseUrl();
                    header('HTTP/1.0 404 Not Found');
                    echo "<html>";
                    echo "<head><title>404 Not Found</title></head>";
                    echo "<body><center><h1>404 Not Found</h1></center>";
                    echo "<center><a href='{$url}'>Back To Homepage</a></center>";
                    echo "</body>";
                    echo "</html>";
                    exit;
                }
            }
        } 

        if ($action == 'noRoute' && $_helper->isEnable()) {

            if($_helper->allowedUserAgent()){
                return;
            }

            $thresholdTime =  $_helper->getThresholdTime();
            $thresholdRequest = $_helper->getThresholdRequest();

            if($_helper->isEnable() && !empty($thresholdTime)){
                $userIp = Mage::helper('spamprotection')->getUserIP();
                if(in_array($userIp,$_helper->getWhitelistedIp())){
                    return;
                }

                $fromDate = Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime("-{$thresholdTime}"));
                $toDate = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $storeId = $_helper->getStoreId();

                $collection =  Mage::getModel('spamprotection/spamprotection')->getCollection();
                $collection->addFieldToSelect('id');
                $collection->addFieldToFilter('ip', $userIp);
                $collection->addFieldToFilter('store_id', $storeId);
                $collection->addFieldToFilter('timestamp', array(
                    'from' => $fromDate,
                    'to' => $toDate,
                    'date' => true,
                ));

                if($collection->getSize() >= $thresholdRequest) {
                    $url = Mage::getBaseUrl();
                    header('HTTP/1.0 404 Not Found');
                    echo "<html>";
                    echo "<head><title>404 Not Found</title></head>";
                    echo "<body><center><h1>404 Not Found</h1></center>";
                    echo "<center><a href='{$url}'>Back To Homepage</a></center>";
                    echo "</body>";
                    echo "</html>";
                    exit;
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function storeCustomerIps(Varien_Event_Observer $observer)
    {
        $_helper = Mage::helper('spamprotection');
        $action = Mage::app()->getRequest()->getActionName();
        if ($action == 'noRoute' && $_helper->isEnable()) {

            if($_helper->allowedUserAgent()){
                return;
            }

            $dateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
            $userIp = $_helper->getUserIP();
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $storeId = $_helper->getStoreId();

            $spamLog = Mage::getModel('spamprotection/spamprotection');
            $spamLog->setIp($userIp);
            $spamLog->setStoreId($storeId);
            $spamLog->setTimestamp($dateTime);
            $spamLog->setVisitedUrl($currentUrl);
            $spamLog->save();
        }
    }
}
