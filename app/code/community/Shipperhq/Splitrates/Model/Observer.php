<?php

/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */

class Shipperhq_Splitrates_Model_Observer extends Mage_Core_Model_Abstract
{

    /**
     * Process multi-origin/group shipping rates
     *
     * @param $observer
     */
    public function preDispatchShippingMethodSave($observer)
    {
        /**
         * @var $controller Mage_Checkout_OnepageController
         */
        $controller = $observer->getControllerAction();

        if ($controller->getRequest()->isPost()) {
            $data = $controller->getRequest()->getPost('shipping_method', '');
            if (!empty($data)) {
                return;
            }
            $data = $controller->getRequest()->getPost();
            $result = $this->_getOnepage()->saveCarriergroupShippingMethod($this->_extractCarrierGroupData($data));
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Splitrates', 'Result from save carriergroup shipping method',
                    $result);
            }

            /**
             * $result will have error data if shipping method is empty otherwise it will contain the shipping method of the merged rate
             */
            if(!is_array($result)) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$controller->getRequest(), 'quote'=>$controller->getOnepage()->getQuote()));
                $result = array();
                $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml($controller)
                );

                if (Mage::helper('core')->isModuleEnabled('EcomDev_CheckItOut')) {
                    $reflection = new ReflectionObject($controller);
                    if ($reflection->hasMethod('_addHashInfo')) {
                        $method = $reflection->getMethod('_addHashInfo');
                        $method->setAccessible(true);
                        $method->invokeArgs($controller, array(&$result));
                        $method->setAccessible(false);
                    }

                }
            }
            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            // Emulate regular postDispatch
            $controller->postDispatch();
        }
    }

    /**
     * Save shipping breakdown per carrier group
     * @param $observer
     */
    public function saveShippingMethodAdmin($observer)
    {
        $requestData = $observer->getRequestModel()->getPost();
        $orderData = array();
        if (isset($requestData['order'])) {
            $orderData = $requestData['order'];
        }
        if(!empty($requestData['shipping_method_flag'])) {
            $orderData = $requestData;
        }
        $quote = $observer->getOrderCreateModel()->getQuote();
        if($quote->isVirtual()) {
            return;
        }
        Mage::helper('shipperhq_shipper')->setQuote($quote);

        if (!empty($orderData['shipping_method_flag'])) {
            $helper = Mage::getSingleton('shipperhq_splitrates/checkout_helper');

            if (!empty($orderData['shipping_method'])) {
                $requestData['order']['shipping_method'] = $orderData['shipping_method'];
            } else {
                $mergedShipMethod = $helper->saveCarriergroupShippingMethod($quote, $this->_extractCarrierGroupData($orderData));
                $requestData['order']['shipping_method'] = $mergedShipMethod;

            }
            $observer->getRequestModel()->setPost($requestData);
        }
    }

    public function predispatchMultishippingShipping($observer)
    {

        $shippingAddresses = $observer->getEvent()->getShippingAddresses();
        $addressShippingMethods = $observer->getEvent()->getAddressShippingMethods();
        $shippingMethods = $observer->getEvent()->getShippingMethods();
        $postData = $observer->getEvent()->getPostData();

            foreach($shippingAddresses as $shippingAddress) {
                if(array_key_exists($shippingAddress->getId(), $addressShippingMethods)) {
                    $splitRates = count($addressShippingMethods[$shippingAddress->getId()]) > 1;
                    $showMerged = $shippingAddress->getCheckoutDisplayMerged();
                    //SHQ16-2023 to continue, it must be split rates and not show merged
                    if(!$splitRates || $showMerged) {
                        continue;
                    }
                    $shippingRateGroups = $shippingAddress->getGroupedAllShippingRates();

                    $shippingDetails = Mage::helper('shipperhq_splitrates')->manuallyMergeShippingRates($shippingRateGroups,
                        $addressShippingMethods[$shippingAddress->getId()], true);

                    if($shippingDetails && !empty($shippingDetails) && !array_key_exists('error', $shippingDetails)) {
                        Mage::helper('shipperhq_splitrates')->createShippingRate($shippingDetails, $shippingAddress);
                        if(!array_key_exists('error', $shippingDetails) && array_key_exists('mergedTitle', $shippingDetails[0])) {
                            $mergedRateCode = 'shipper_'.$shippingDetails[0]['mergedTitle'];
                            $shippingMethods[$shippingAddress->getId()] = $mergedRateCode;
                            $mergedShippingRate = $shippingAddress->getShippingRateByCode($mergedRateCode);
                            if($mergedShippingRate) {
                                $carriergroupShippingDetail = array();
                                foreach($shippingDetails as $cgDetails) {
                                    $thisShipDetails =  Mage::helper('shipperhq_shipper')->decodeShippingDetails($cgDetails['shipping_details']);
                                    $carriergroup = $cgDetails['carriergroup'];
                                    $multiCheckoutCgKey = 'ma'.$shippingAddress->getId() .'ZZ' .$carriergroup;
                                    $carrier_code = $cgDetails['carrier_code'];
                                    $carrier_type = $cgDetails['carrier_type'];
                                    if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup') && Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($carrier_type)) {
                                        if(array_key_exists('location_id_'.$carrier_code .'_'.$multiCheckoutCgKey, $postData)) {
                                            $thisShipDetails['location_id'] = $postData['location_id_'.$carrier_code .'_'.$multiCheckoutCgKey];
                                            $thisShipDetails['pickup_date'] = $postData['pickup_date_'.$carrier_code .'_'.$multiCheckoutCgKey];
                                            if(array_key_exists('pickup_slot_'.$multiCheckoutCgKey, $postData) &&
                                                $postData['pickup_slot_'.$carrier_code.'_'.$multiCheckoutCgKey] != '') {
                                                $thisShipDetails['pickup_slot'] = $postData['pickup_slot_'.$carrier_code.'_'.$multiCheckoutCgKey];
                                            }
                                        }
                                    }
                                    if(array_key_exists('del_date_'.$carrier_code.'_'.$multiCheckoutCgKey, $postData)) {
                                        $thisShipDetails['delivery_date'] = $postData['del_date_'.$carrier_code.'_'.$multiCheckoutCgKey];
                                    }
                                    $carriergroupShippingDetail[] = $thisShipDetails;
                                }
                                if($carriergroupShippingDetail) {
                                    $encodedShipDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($carriergroupShippingDetail);
                                   $mergedShippingRate->setCarriergroupShippingDetails($encodedShipDetails)
                                        ->save();
                                }
                            }


                        }

                    }
                }

            }
//            $controller->getRequest()->setPost('shipping_method',$shippingMethods);
//        }
    }

    /*
     *
     */
    public function preDispatchSetMethodsSeparate($observer)
    {

        $controller = $observer->getControllerAction();
        $quote = $this->_getOnepage()->getQuote();
        $quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage($this->_getOnepage()->getQuote());

       if ($controller->getRequest()->isPost()) {
            $shippingMethod = $controller->getRequest()->getPost('shipping_method', '');
            if (!empty($shippingMethod)) {
                return;
            }

           $data = $controller->getRequest()->getPost();

            $shipMethodsCarrierGroupsSelect = $quoteStorage->getCarriergroupSelected();
            if(is_null($shipMethodsCarrierGroupsSelect)) {
                return;
            }
            $shippingMethod = array();
            foreach($shipMethodsCarrierGroupsSelect as $carrierGroupId => $selectedMethod) {
                if($carrierGroupId == '' || is_null($carrierGroupId)) {
                    continue;
                }
                $shippingMethod['shipping_method_'.$carrierGroupId] = $selectedMethod;
            }

           if (Mage::helper('shipperhq_shipper')->isDebug()) {
               Mage::helper('wsalogger/log')->postDebug('Shipperhq_Splitrates', 'Selected shipping methods for carrier groups are : ',
                   $shippingMethod);
           }

            $shippingAddress = $quote->getShippingAddress();

            if($shippingAddress) {
                $shippingRateGroups = $shippingAddress->getGroupedAllShippingRates();

                $shippingDetails = Mage::helper('shipperhq_splitrates')->manuallyMergeShippingRates($shippingRateGroups, $shippingMethod, true);
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Splitrates', 'Creating merged rate using: ',
                        $shippingDetails);
                }

                if($shippingDetails && !empty($shippingDetails) && !array_key_exists('error', $shippingDetails)) {
                    $this->saveCalendarDates($shippingAddress, $shippingDetails);
                    Mage::helper('shipperhq_splitrates')->createShippingRate($shippingDetails, $shippingAddress);

                    if(!array_key_exists('error', $shippingDetails) && array_key_exists('mergedTitle', $shippingDetails[0])) {
                        $data['shipping_method'] = 'shipper_'.$shippingDetails[0]['mergedTitle'];
                        $controller->getRequest()->setPost($data);
                    }
                }
            }

        }
    }

    protected function _extractCarrierGroupData($data)
    {
        $carrierGroupData = array();
        $collectThis = array('shipping_method');
        $pickupData = array( 'pickup_date', 'pickup_slot', 'location_id');
        $calendarData = array('del_date_', 'del_slot_');
        $collectThis = array_merge($collectThis, $pickupData, $calendarData);
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight')){
            $optionCodes = Mage::helper('shipperhq_freight')->getAllPossibleOptions();
            $collectThis = array_merge($collectThis, $optionCodes);

        }
        foreach($data as $key => $value) {
            foreach($collectThis as $code) {
                if(strstr($key, $code)) {
                    $carrierGroupData[$key] = $value;
                }
            }
        }
        $this->retrieveCustomAdminShippingData($data);
        return $carrierGroupData;
    }

    protected function retrieveCustomAdminShippingData($data)
    {
        $found = false;
        $customCarrierGroupData = array();
        foreach($data as $key => $value) {
            if((strstr($key, 'customCarrier') || strstr($key, 'customPrice')) && $value != '') {
                $found = true;
                $keyParts = explode('_', $key);
                $carrierGroupName = '';
                if(isset($data[$keyParts[1]])) {
                    $carrierGroupName = $data[$keyParts[1]];
                }
                if(array_key_exists($keyParts[1], $customCarrierGroupData)) {
                    $shipArray = $customCarrierGroupData[$keyParts[1]];
                    $shipArray[$keyParts[0]] = $value;
                    $shipArray['carriergroup'] = $carrierGroupName;
                    $customCarrierGroupData[$keyParts[1]] = $shipArray;
                }
                else {
                    $customCarrierGroupData[$keyParts[1]] = array($keyParts[0] => $value, 'carriergroup' => $carrierGroupName);
                }

            }
        }

        if ($found) {
            $shippingAddress = Mage::helper('shipperhq_shipper')->getQuote()->getShippingAddress();
            foreach($customCarrierGroupData as $carrierGroupId => $data){
                Mage::helper('shipperhq_shipper')->cleanDownRatesCollection($shippingAddress, 'shipperadmin', $carrierGroupId);
            }
            Mage::register('shqadminship_data', new Varien_Object($customCarrierGroupData));
            $storedLimitCarrier = $shippingAddress->getLimitCarrier();
            $shippingAddress->setLimitCarrier('shipperadmin');
            $rateFound = $shippingAddress->requestShippingRates();
            $shippingAddress->setLimitCarrier($storedLimitCarrier);
        } else {
            Mage::unregister('shqadminship_data');
        }
    }

    protected function saveCalendarDates($shippingAddress, $shippingDetails)
    {
        $existingDates =  Mage::helper('shipperhq_shipper')->getQuoteStorage()->getCalendarDatesSaved();
        if(is_array($shippingDetails)) {
            foreach($shippingDetails as $detail)
            {
                $datesToBeSaved = array();
                if(is_array($detail) && array_key_exists('shipping_details', $detail)) {
                    $unEncoded =  Mage::helper('shipperhq_shipper')->decodeShippingDetails($detail['shipping_details']);
                    if(is_array($unEncoded) && array_key_exists('delivery_date', $unEncoded)
                        && array_key_exists('dispatch_date', $unEncoded)) {
                            $datesToBeSaved['dispatch_date'] = $unEncoded['dispatch_date'];
                            $datesToBeSaved['delivery_date'] = $unEncoded['delivery_date'];
                            $existingDates[$detail['carriergroup']] = $datesToBeSaved;
                    }
                }
            }
        }

        Mage::helper('shipperhq_shipper')->getQuoteStorage()->setCalendarDatesSaved($existingDates);
    }

    protected function _getOnepage()
    {
        return Mage::getSingleton('shipperhq_splitrates/checkout_type_onepage');
    }

    /**
     * Get payment method step html
     *
     * @param Mage_Checkout_OnepageController $controller
     * @return string
     */
    protected function _getPaymentMethodsHtml($controller)
    {
        $layout = $controller->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_paymentmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
}