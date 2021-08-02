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

class Shipperhq_Splitrates_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Determine if merged checkout is configured and valid
     *
     * @return boolean
     */
    public function isMergedCheckout()
    {
        $globalSettings = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if(is_array($globalSettings) && array_key_exists('dropshipShowMergedRates', $globalSettings)) {
            return $globalSettings['dropshipShowMergedRates'];
        }
        return true;

    }

    /*
     * Look at value of merged checkout on address and number items in cart
     *
     * @return boolean
     */
    public function displayMergedCheckout()
    {
        $configuredMergedCheckout = $this->isMergedCheckout();
        if(count($this->getQuote()->getAllItems()) == 1) {
            return true;
        }
        return $configuredMergedCheckout;
    }


    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::helper('shipperhq_shipper')->getQuote();
    }

    public function parseCarrierGroupRates($shipperResponse, $rawRequest)
    {
        $globals = (array)$shipperResponse->globalSettings;
        $responseSummary = (array)$shipperResponse->responseSummary;

        $carrierGroups = $shipperResponse->carrierGroups;
        $mergedResponse = $shipperResponse->mergedRateResponse;
        $showMerged = $shipperResponse->globalSettings->dropshipShowMergedRates == '1'? true : false;

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Splitrates', 'Rates response dipslay merged rates flag: ',
                $showMerged);
        }

        $mergedDescription = $globals['dropshipDescription'];
        $mergedCarrierTitle = $globals['dropshipTitle'];

        $checkoutFlag = Mage::helper('shipperhq_shipper')->isCheckout();
        $carriergroupDescriber = $shipperResponse->globalSettings->carrierGroupDescription;
        if($carriergroupDescriber != '') {
            Mage::helper('shipperhq_shipper')->saveConfig(Shipperhq_Shipper_Helper_Data::SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH,
                $carriergroupDescriber);
        }

        $this->getQuote()->getShippingAddress()->setSplitRates(1);
        $this->getQuote()->getBillingAddress()->setSplitRates(1);

        $this->getQuote()->getShippingAddress()->setCheckoutDisplayMerged($showMerged);
        $this->getQuote()->getBillingAddress()->setCheckoutDisplayMerged($showMerged);
        //default to merged rates unless we are in the checkout
        if(!$checkoutFlag) {
            $showMerged = true;
        }

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('Shipperhq_Splitrates', 'Display merged rates in checkout set to ',
                $showMerged);
        }

        $ratesArray = array();
        $mergedRates = array();
        $itemsInRate = array();

        $splitRateCarriergroupDetail = array();
        //Collect split rates into array
        foreach($carrierGroups as $carrierGroup)
        {
            if(isset($carrierGroup->carrierGroupDetail)) {
                $carrierGroupDetail = (array)$carrierGroup->carrierGroupDetail;
            }
            else {
                $carrierGroupDetail = array();
            }
            $globals = array_merge($globals, $carrierGroupDetail);
            $carrierGroupDetail[ 'mergedTitle']   = $mergedCarrierTitle;
            $carrierGroupDetail['mergedDescription'] = $mergedDescription;
            $carrierGroupDetail['transaction'] = $responseSummary['transactionId'];
            Mage::unregister('shipperhq_transaction');
            Mage::register('shipperhq_transaction', $responseSummary['transactionId']);
            $carriergroupId = '';
            if(array_key_exists('carrierGroupId',$carrierGroupDetail)) {
                $carriergroupId = $carrierGroupDetail['carrierGroupId'];
            }

            foreach($carrierGroup->carrierRates as $carrierRate) {
                $carrierResultWithRates = Mage::helper('shipperhq_shipper')->chooseCarrierAndProcess(
                    $carrierRate, $carriergroupId, $carrierGroupDetail, $carrierGroup->products, true);

                if(is_array($carrierResultWithRates) && array_key_exists('rates', $carrierResultWithRates)) {
                    foreach($carrierResultWithRates['rates'] as $oneRate) {
                        $cgid = $oneRate['carriergroup_detail']['carrierGroupId'];
                        $splitRateCarriergroupDetail[$cgid][$carrierResultWithRates['code']][$oneRate['method_code']] =  $oneRate['carriergroup_detail'];
                    }
                }

                if(isset($carrierGroup->products)) {
                    $products = array();
                    foreach($carrierGroup->products as $product) {
                        $products[$product->sku] = array('qty' =>$product->qty);
                        if(!array_key_exists($product->sku, $itemsInRate)){
                            $itemsInRate[$product->sku] = array('carrierGroupId' => $carriergroupId,
                                'name' =>$carrierGroupDetail['name']
                                );
                        }
                    }
                    $carrierResultWithRates['products'] = $products;
                    $this->setCarrierGroupOnQuoteItems($carrierGroup->products, $rawRequest, $carrierGroupDetail);

                }
                $ratesArray[] = $carrierResultWithRates;
            }
        }
        Mage::helper('shipperhq_shipper')->getQuoteStorage($this->getQuote())
            ->setShipperGlobal($globals);

        if(!$showMerged) {

            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Splitrates', 'Returning split rates',
                    $ratesArray);
            }
            return $ratesArray;
        }
        $mergedCarrierResultWithRates = array();
        if(isset($mergedResponse->error)) {
            $mergedCarrierResultWithRates['error'] = (array)$mergedResponse->error;
            $ourCarrierCode = Mage::helper('shipperhq_shipper')->getOurCarrierCode();
            $mergedCarrierResultWithRates['code'] = $ourCarrierCode;
            $mergedCarrierResultWithRates['title'] = Mage::getStoreConfig('carriers/'.$ourCarrierCode.'/title');
            $mergedRates[] = $mergedCarrierResultWithRates;
        }

        foreach($mergedResponse->carrierRates as $carrierRate)
        {
            $mergedCGDetail= array();
            $mergedCGDetail['transaction'] = $responseSummary['transactionId'];
            $mergedCarrierResultWithRates = Mage::helper('shipperhq_shipper')->chooseCarrierAndProcess($carrierRate, null, $mergedCGDetail);
           if(isset($carrierRate->rates)) {
               if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight')) {
                   $freightRate = Mage::helper('shipperhq_freight')->isFreightEnabledMergedRate($carrierRate->carrierCode);
               }
                foreach($carrierRate->rates as $oneRate) {
                    if(isset($oneRate->rateBreakdownList)){
                        $carrierGroupShippingDetail = array();
                        $rateBreakdown = $oneRate->rateBreakdownList;

                        foreach($rateBreakdown as $rateInMergedRate) {
                            if(array_key_exists($rateInMergedRate->carrierGroupId, $splitRateCarriergroupDetail)) {
                                if(array_key_exists($rateInMergedRate->carrierCode, $splitRateCarriergroupDetail[$rateInMergedRate->carrierGroupId])
                                && array_key_exists($rateInMergedRate->methodCode, $splitRateCarriergroupDetail[$rateInMergedRate->carrierGroupId][$rateInMergedRate->carrierCode])) {
                                    $carrierGroupShippingDetail[]= $splitRateCarriergroupDetail[$rateInMergedRate->carrierGroupId][$rateInMergedRate->carrierCode][$rateInMergedRate->methodCode];
                                }
                            }

                        }
                        foreach($mergedCarrierResultWithRates['rates'] as $key => $rateToAdd) {
                            if($rateToAdd['method_code'] != $oneRate->code) {
                                continue;
                            }
                            $rateToAdd['carriergroup_detail'] = $carrierGroupShippingDetail;
                            if($freightRate) {
                                $rateToAdd['freight_rate'] = $freightRate;
                            }
                            $mergedCarrierResultWithRates['rates'][$key] = $rateToAdd;
                        }
                    }

                }
                $mergedRates[] = $mergedCarrierResultWithRates;
            }
        }

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('Shipperhq_Splitrates', 'Returning merged rates',
                $mergedRates);
        }
        return $mergedRates;

    }

    public function setCarrierGroupOnQuoteItems($rateItems, $rawRequest, $carriergroupDetails)
    {
        $quoteItems = $this->getQuote()->getAllItems();
        $items = $rawRequest->getAllItems();

        foreach($rateItems as $item) {
            $item = (array)$item;
            $sku = $item['sku'];
            $itemId = array_key_exists('id', $item) ? $item['id'] : false;
            foreach($quoteItems as $item)
            {
                if($item->getSku() == $sku && (!$itemId || $item->getId() == $itemId)) {
                    $item->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                    $item->setCarriergroup($carriergroupDetails['name']);
                    if($parentItem = $item->getParentItem()) {
                        $parentItem->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                        $parentItem->setCarriergroup($carriergroupDetails['name']);

                    }
                }
            }

            foreach($items as $quoteItem)
            {
                if($quoteItem->getSku() == $sku && (!$itemId || $quoteItem->getQuoteItemId() == $itemId)) {
                    $quoteItem->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                    $quoteItem->setCarriergroup($carriergroupDetails['name']);
                    if($parentItem = $quoteItem->getParentItem()) {
                        $parentItem->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                        $parentItem->setCarriergroup($carriergroupDetails['name']);
                    }
                }
            }
        }
    }

    public function collectMergedRates($storeId, $ratesToAdd) {
        $shipping = Mage::getModel('shipping/shipping');
        $shipping->resetResult();
        $carrier = $shipping->getCarrierByCode("shipper", $storeId);
        $shipping->getResult()->append($carrier->createMergedRate($ratesToAdd));
        return $shipping->getResult();
    }

    private function isValidCarrierGroup($carrierGroupId, $carrierGroupCountArry)
    {
        if (is_numeric($carrierGroupId) && !in_array($carrierGroupId,$carrierGroupCountArry)) {
            return true;
        }

        if (strpos($carrierGroupId, 'XXXX') !== false) {
            return true;
        }

        return false;
    }

    /*
     *
     *
     */
    public function manuallyMergeShippingRates($shippingRateGroups, $shippingMethod, $ignoreMissingSelections = false)
    {
        $shipMethodCount=0;
        $shippingDetails = array();
        $carrierGroupCountArry=array();

        foreach ($shippingRateGroups as $rates) {
            foreach ($rates as $rate) {
                if($this->isValidCarrierGroup($rate->getCarriergroupId(), $carrierGroupCountArry)){
                    $carrierGroupCountArry[] = $rate->getCarriergroupId();
                }
            }
        }

        $carrierGroupCountArry = array_unique($carrierGroupCountArry);

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Splitrates', 'Manually merging shipping rates',
                $shippingMethod);
        }
        //gather selected date information to add to carriergroup_detail on rate
        $dateAndDeliverySelections = array();
        foreach($shippingMethod as $field => $value) {
            if($value != '' && !is_null($value)) {
                if(strstr($field, 'pickup_date') || strstr($field, 'pickup_slot')) {
                    $pieces 	= explode('_',$field);
                    $carriergroupId	= $pieces[3];
                    if(!array_key_exists($carriergroupId, $dateAndDeliverySelections)) {
                        $dateAndDeliverySelections[$carriergroupId] = array();
                    }
                    $key = strstr($field, 'pickup_date') ? 'pickup_date' : 'pickup_slot';
                    $dateAndDeliverySelections[$carriergroupId][$key] = $value;
                    if(strstr($field, 'pickup_date'))    {
                        $dateAndDeliverySelections[$carriergroupId]['delivery_date'] = $value;
                        $dateAndDeliverySelections[$carriergroupId]['dispatch_date'] = $value;
                    }
                }

                elseif(strstr($field, 'location_id')) {
                    $pieces 	= explode('_',$field);
                    if(is_array($pieces) && array_key_exists(3, $pieces)) {
                        $carriergroupId	= $pieces[3];
                        if(!array_key_exists($carriergroupId, $dateAndDeliverySelections)) {
                            $dateAndDeliverySelections[$carriergroupId] = array();
                        }
                        $dateAndDeliverySelections[$carriergroupId]['location_id'] = $value;
                        $pickupCarrierCode = $shippingMethod['shipping_method_'.$carriergroupId];
                        if($pickupCarrierCode) {
                            $codeAndMethod = explode('_', $pickupCarrierCode);
                            $carrierCode = $codeAndMethod[0];
                            $pickupLocation = Mage::helper('shipperhq_pickup')->getLocationDetails($carriergroupId,$carrierCode, $value);
                            if($pickupLocation) {
                                $dateAndDeliverySelections[$carriergroupId]['location_name'] = $pickupLocation['pickupName'];
                            }
                        }
                    }
                }
                elseif(strstr($field, 'del_date_') || strstr($field, 'del_slot')) {
                    $pieces 	= explode('_',$field);
                    if(is_array($pieces) && array_key_exists(3, $pieces)) {
                        $carriergroupId	= $pieces[3];
                        if(!array_key_exists($carriergroupId, $dateAndDeliverySelections)) {
                            $dateAndDeliverySelections[$carriergroupId] = array();
                        }
                        $key = strstr($field, 'del_date') ? 'del_date' : 'del_slot';
                        $dateAndDeliverySelections[$carriergroupId][$key] = $value;

                        $dateAndDeliverySelections[$carriergroupId]['del_date'] = $value;
                        if(strstr($field, 'del_date'))    {
                            $this->checkDispatchDateSelections($carriergroupId, $dateAndDeliverySelections[$carriergroupId]);
                        }
                    }
                }
            }
        }

        // need to store each rate for each warehouse
        foreach ($shippingMethod as $indMethod=>$shipMethod) {

            if(!strstr($indMethod, 'shipping_method') || $indMethod == 'shipping_method_flag') {
                continue;
            }
            $shipMethodCount++;

            // extract carriergroup from text
            $pieces 	= explode('_',$indMethod);
            $carriergroupId	= $pieces[2];
            $rateSplit 	= explode('_',$shipMethod);
            $methodCode = $rateSplit[0];

            $found=false;
            if (array_key_exists($methodCode,$shippingRateGroups)) {
                $rates = $shippingRateGroups[$methodCode];
                foreach ($rates as $indRate) {

                    if ($carriergroupId==$indRate->getCarriergroupId() && $shipMethod==$indRate->getCode()) {
                        $rate = $indRate;
                        $found=true;
                        break;
                    }
                }
            }

            if (!$found) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postWarning('Shipperhq_Splitrates', 'Cannot merge shipping rates',
                       'attempting to find carrier group ID : ' .$carriergroupId .' and ship method ' .$shipMethod .'. Ensure carrier group IDs are set on rates');
                }
                return array('error' => -1, 'message' => $this->__('Please select shipping method(s).'));
            }

            $carrierGroupDetails =  Mage::helper('shipperhq_shipper')->decodeShippingDetails(
                $rate->getCarriergroupShippingDetails());
            $globals =  Mage::helper('shipperhq_shipper')->getGlobalSettings();
            $rateToAdd = array (
                'carriergroup'		=> $carriergroupId,
                'code'			=> $shipMethod,
                'carrier_code'  => $methodCode,
                'carrier_type'  => $rate->getCarrierType(),
                'price'			=> (float)$rate->getPrice(),
                'cost'			=> (float)$rate->getCost(),
                'carrierTitle'	=> $rate->getCarrierTitle(),
                'methodTitle'	=> $rate->getMethodTitle(),
                'freightQuoteId'=> $rate->getFreightQuoteId(),
                'mergedTitle'   => $globals['dropshipTitle'],
                'mergedDescription' => $globals['dropshipDescription'],
              //  'shipping_details' => $rate->getCarriergroupShippingDetails()
            );
            $existingShippingDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails(
                $rate->getCarriergroupShippingDetails());

            if(array_key_exists($carriergroupId, $dateAndDeliverySelections)) {
                $existingShippingDetails = array_merge($existingShippingDetails, $dateAndDeliverySelections[$carriergroupId]);
            }
            $rateToAdd['shipping_details'] =  Mage::helper('shipperhq_shipper')->encodeShippingDetails($existingShippingDetails);
            if (!is_null($rate->getFreightQuoteId())) {
                $rateToAdd['freightQuoteId']=$rate->getFreightQuoteId();
            }
            $shippingDetails[] = $rateToAdd;
        }

        if (!$ignoreMissingSelections && ($shipMethodCount==0 || $shipMethodCount!= count($carrierGroupCountArry))) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('shipperhq_splitrates')->__('Please specify shipping method(s) for each group.')
            );
            return $res;
        }
        return $shippingDetails;
    }

    /*
     *
    * Create a single shipping rate that combines the individual rates selected at checkout
    *
    */
    public function createShippingRate($shippingDetails, &$shippingAddress) {
        $totalPrice=0;
        $freightQuoteId='';
        $mergedTitle = '';
        $mergedDescription = '';

        $carriergroupDetail = array();

        foreach ($shippingDetails as $details) {
            $totalPrice += $details['price'];
            if (is_array($details) && array_key_exists('freightQuoteId', $details)
                && $details['freightQuoteId']!='') {
                $freightQuoteId=$details['freightQuoteId'];
            }
            $mergedTitle = $mergedDescription = '';
            if (is_array($details) && array_key_exists('mergedTitle', $details)) {
                $mergedTitle = $details['mergedTitle'];
            }
            if (is_array($details) && array_key_exists('mergedDescription', $details)) {
                $mergedDescription = $details['mergedDescription'];
            }
            if(is_array($details) && array_key_exists('shipping_details', $details)) {
                $carriergroupDetail[] = $details['shipping_details'];
            }
        }

        $shippingRates = $shippingAddress->getAllShippingRates();
        $found = false;

        foreach ($shippingRates as $shippingRate) {
            if ($shippingRate->getCarrier()=='shipper' && $mergedTitle==$shippingRate->getMethod()) {
                $shippingRate->setPrice($totalPrice);
                $found=true;
                break;
            }
        }

        if (!$found) {
            $mergedRatesToAdd = array ( array (
                'price'				=> $totalPrice,
                'title'				=> $mergedTitle,
                'freight_quote_id' 	=> $freightQuoteId,
                'expected_delivery'	=> '',
                'dispatch_date'		=> '',
                 'mergedTitle'      => $mergedTitle,
                'mergedDescription' => $mergedDescription
            ));

            $result = Mage::helper('shipperhq_splitrates')
                ->collectMergedRates($this->getQuote()->getStore()->getId(),$mergedRatesToAdd);


            if ($result) {
                $shippingRates = $result->getAllRates();

                foreach ($shippingRates as $shippingRate) {
                    $rate = Mage::getModel('shipperhq_shipper/sales_quote_address_rate')
                        ->importShippingRate($shippingRate);
                    $shippingAddress->addShippingRate($rate);
                    $shippingAddress->setShippingMethod($rate->getCode());
                    $shippingAddress->setShippingAmount($rate->getPrice());
                    $shippingAddress->save();
                }

            }


        }
        $shippingAddress->setCarriergroupShippingDetails
            (Mage::helper('shipperhq_shipper')->encodeShippingDetails($carriergroupDetail));
    }

    public function checkDispatchDateSelections($carrierGroupId, &$dateSelections)
    {
        $calendarDatesFromSession = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getCalendarDatesSaved();
        if(is_array($calendarDatesFromSession) && array_key_exists($carrierGroupId, $calendarDatesFromSession)) {
            $datesChosen = $calendarDatesFromSession[$carrierGroupId];
            if(Date('Y-m-d', (strtotime($datesChosen['delivery_date']))) != Date('Y-m-d', strtotime($dateSelections['del_date'])) ){
                $dateSelections['dispatch_date'] = $datesChosen['dispatch_date'];
            }

        }
    }

}