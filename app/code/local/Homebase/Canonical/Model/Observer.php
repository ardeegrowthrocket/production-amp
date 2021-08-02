<?php
class Homebase_Canonical_Model_Observer
{

    public function addCanonicalToCms(Varien_Event_Observer $observer)
    {
        $_block = $observer->getBlock();
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $_block->getRequest();
        $module = $_request->getControllerModule();
        $action =  $_request->getActionName();
        /** @var Mage_Core_Helper_Url $_url */
        $_url = Mage::helper('core/url');
        $href = preg_replace('/\?.*/', '', $_url->getCurrentUrl());

        if($_block instanceof Mage_Page_Block_Html_Head){
            if($module === 'Mage_Cms' && $action != 'noRoute'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                $_linkEl = $_dom->createElement('link');
                $_linkEl->setAttribute('rel','canonical');
                $_linkEl->setAttribute('href',$href);


                $headElement = $_dom->getElementsByTagName('head');
                /** @var DOMElement $head */
                foreach($headElement as $head){
                    $head->appendChild($_linkEl);
                    break;
                }
                $transport['html'] = $_dom->saveHTML();
            }
        }
    }

}
