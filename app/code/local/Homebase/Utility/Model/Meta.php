<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/07/2018
 * Time: 10:06 AM
 */
class Homebase_Utility_Model_Meta{
    public function modifyRobots($observer){
        $block = $observer->getBlock();
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $block->getRequest();
        $storeCode = $request->getStoreCodeFromPath();
        $controller = $request->getControllerName();
        $module = $request->getControllerModule();
        $action = $request->getActionName();
        $pagination = $request->getParam('p', null);
        $dir = $request->getParam('dir',null);
        $order = $request->getParam('order',null);

        if($block instanceof  Mage_Page_Block_Html_Head){
            if($module == 'Homebase_Auto'){
                if($storeCode == 'spp_en'){
                    $transport = $observer->getTransport();
                    $html = $transport['html'];
                    $dom = new DOMDocument();
                    $dom->loadHTML($html);
                    $xpath = new DOMXPath($dom);
                    if(!is_null($pagination)){
                        $robots = $xpath->query('//meta[@name="robots"]');
                        /** @var DOMElement $robot */
                        foreach($robots as $robot){
                            $robot->setAttribute('content','NOINDEX,FOLLOW');
                        }
                        $transport['html'] = $dom->saveHTML();
                    }

                    if(!is_null($dir) && !is_null($order)){
                        $robots = $xpath->query('//meta[@name="robots"]');
                        $parentNode = null;
                        /** @var DOMElement $robot */
                        foreach($robots as $robot){
                            $parentNode = $robot->parentNode;
                        }
                        foreach($robots as $robot){
                            if(!is_null($parentNode)){
                                $parentNode->removeChild($robot);
                            }
                        }
                        $transport['html'] = $dom->saveHTML();
                    }
                }
            }else if($module =='Smartwave_QuickView'){
                if($storeCode == 'default'){
                    if($action == 'view' && $controller == 'index'){
                        $transport = $observer->getTransport();
                        $html = $transport['html'];
                        $dom = new DOMDocument();
                        $dom->loadHTML($html);
                        $xpath = new DOMXPath($dom);
                        $robots = $xpath->query('//meta[@name="robots"]');
                        /** @var DOMElement $robot */
                        foreach($robots as $robot){
                            $robot->setAttribute('content','NOINDEX,NOFOLLOW');
                        }
                        $transport['html'] = $dom->saveHTML();
                    }
                }
            }
        }

    }
}