<?php
//Possibly remove these
include_once 'ShipperHQ/User/Credentials.php';
include_once 'ShipperHQ/User/SiteDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/CustomerDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/ShipDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/RateRequest.php';
include_once 'ShipperHQ/WS/Request/Rate/InfoRequest.php';
include_once 'ShipperHQ/Shipping/Address.php';

include_once 'ShipperHQ/WS/Client/WebServiceClient.php';


class Shipperhq_Freight_Model_Service_Accessorials
{
    protected static $_debug;
    protected $quote;
    protected $quoteStorage;

    protected $_shipperWSInstance;
    protected $_cartMapperInstance;

    protected $_rates;


    public function retrieveAccessorials($quote)
    {
        //get data for cache and request
        self::$_debug = Mage::helper('shipperhq_freight')->isDebug();
        $this->quote = $quote;
        $this->quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage($this->quote);

        //check for result in cache and return
        $params = Mage::helper('shipperhq_freight')->getAccessorialParams($this->quote);

        $cachedResponse = $this->_getCachedAccessorials($params);
        if(!is_null($cachedResponse)) {
            if(Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq Freight', 'Accessorials cached response'
                    ,$cachedResponse);
            }
            return $cachedResponse;
        }

        //query webservice
        $response = $this->queryAccessorials($params, $this->quote);
        //store to cache
        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Freight', 'Accessorials retrieved'
                ,$response);
        }
        return $response;
    }

    protected function queryAccessorials($params, $quote)
    {
        if (empty($this->_shipperWSInstance)) {
            $this->_shipperWSInstance = new \ShipperHQ\WS\Client\WebServiceClient();
        }

        $accessorialsRequest = $this->_getCartMapper()->
            getCartTranslation($quote);
        $debugRequest = $accessorialsRequest;

        $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
        $response = $this->_shipperWSInstance->sendAndReceive($accessorialsRequest,
            Mage::helper('shipperhq_shipper')->getRateGatewayUrl(), $timeout);
        $debugData = array('request' => $debugRequest, 'response' => $response['result']);
        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Freight', 'Accessorials raw response'
                ,$debugData);
        }
        $result = array_key_exists('result', $response) ? $this->processResponse($response['result']): false;
        if($result) {
            $this->setCachedAccessorials($params, $result, false);
        }
        return $result;

    }

    public function processResponse($response)
    {
        $debugData = array('response' => $response);
        $result = false;
        //first check and save globals for display purposes
        if(is_object($response) && isset($response->globalSettings)) {
            $globals = (array)$response->globalSettings;
            Mage::helper('shipperhq_shipper')->getQuoteStorage()->setShipperGlobal($globals);
        }

        // If no rates are found return error message
        if (!is_object($response)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ did not return a response',
                    $debugData);
            }
            return false;
        }
        elseif(!empty($response->errors)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ returned an error',
                    $debugData);
            }
            return false;
        }
        $availableOptions = array();

        if(isset($response->carrierGroups)) {
            $carrierGroups = $response->carrierGroups;
            $single = count($carrierGroups) == 1;
            foreach($carrierGroups as $carrierGroup)
            {
                $carrierGroupDetail = (array)$carrierGroup->carrierGroupDetail;
                $carrierGroupId = $single ? 0 :$carrierGroupDetail['carrierGroupId'];

                foreach($carrierGroup->carrierRates as $carrierRate) {
                    if ($carrierRate->carrierType == "custom" && count($carrierRate->rates) == 0) {
                        continue;
                    }

                    $carrierCode = $carrierRate->carrierCode;
                    if(isset($carrierRate->availableOptions)) {
                        $options = (array)$carrierRate->availableOptions;
                        $options['carrier_id'] = $carrierRate->carrierId;
                        $availableOptions[$carrierGroupId][$carrierCode] = $options;
                    }
                }
            }
        }

        //SHQ16-1605 merged rates
        if(isset($response->mergedRateResponse)) {
            foreach($response->mergedRateResponse->carrierRates as $carrierRate) {
                if (isset($carrierRate->rates)) {
                    foreach ($carrierRate->rates as $oneRate) {
                        $options = array();
                        if (isset($oneRate->rateBreakdownList)) {
                            $rateBreakdown = $oneRate->rateBreakdownList;
                            foreach ($rateBreakdown as $rateInMergedRate) {
                                if (array_key_exists($rateInMergedRate->carrierGroupId, $availableOptions)) {
                                    if (array_key_exists($rateInMergedRate->carrierCode, $availableOptions[$rateInMergedRate->carrierGroupId])) {
                                        $options = array_merge($options, $availableOptions[$rateInMergedRate->carrierGroupId][$rateInMergedRate->carrierCode]);
                                    }
                                }

                            }
                        }
                    }
                    $availableOptions[0][$carrierRate->carrierCode] = $options;
                }
            }
        }
        if(count($availableOptions) > 0) {
            $result = $availableOptions;
        }
        return $result;
    }

    public function getAccessorialRates($quote, $params)
    {

        $this->_setAccessorials($quote, $params);

        $this->_rates = $this->getShippingRates($quote->getShippingAddress());
        $requestedCode =  $params['carrier_code'];
        $requestedCarrierGroup = $params['carrier_group'];
        $isOsc = $params['onestepcheckout'] == 'true';
        $resultSet = array();
        $newRates= false;

        foreach ($this->_rates as $code => $rates) {
            if ($code == $requestedCode) {
                foreach ($rates as $rate) {
                   if($requestedCarrierGroup && $requestedCarrierGroup != ''
                       && $rate->getCarriergroupId() != $requestedCarrierGroup) {
                        continue;
                    }
                    $_excl = $this->_getShippingPrice($quote, $rate->getPrice(), Mage::helper('tax')->displayShippingPriceIncludingTax(), false);
                    $_incl = $this->_getShippingPrice($quote, $rate->getPrice(), true, false);

                    $label =  $this->getMethodTitle( $rate->getMethodTitle(),  $rate->getMethodDescription(), !$isOsc) .' ' .$_excl;

                    if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl)
                    {
                        $label .= ' (' .Mage::helper('shipperhq_shipper')->__('Incl. Tax') .' ' .$_incl .')';
                    }
                    $newRates[$rate->getCode()] = array(
                        //'code' 			=> ,
                        'price' 				=> $this->_getShippingPrice($quote, $rate->getPrice(), Mage::helper('tax')->displayShippingPriceIncludingTax(), false),
                        //	'method_title' 			=> $rate->getMethodTitle(),
                        'method_description' 	=> $rate->getMethodTitle(),
                        'label'                 => $label
                    );
                }
            }
        }

        $resultSet['shipping_rates'] = $newRates;
        $resultSet['carrier_code'] = $requestedCode;
        $resultSet['carrier_group'] = $requestedCarrierGroup;

        return $resultSet;
    }

    protected function getMethodTitle($methodTitle, $methodDescription, $includeContainer)
    {
        return Mage::helper('shipperhq_shipper')->getMethodTitle($methodTitle, $methodDescription, $includeContainer);
    }

    protected function getShippingRates($address)
    {
        if (empty($this->_rates)) {
            $groups = $address->getGroupedAllShippingRates();
            return $this->_rates = $groups;
        }

        return $this->_rates;
    }

    protected function _setAccessorials($quote, $params)
    {
        $requestedCode = $params['carrier_code'];
        $requestCarrierGroup = $params['carrier_group'];
        $requestedDate = isset($params['date_selected']) ? $params['date_selected'] : false;
        $allAccessorials = Mage::helper('shipperhq_freight')->getAllPossibleOptions();
        $address = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        //SHQ16-1605
        $selectedFreightOptions = array('carriergroup_id' => $requestCarrierGroup,
            'carrier_code' => $requestedCode);
        foreach ($allAccessorials as $accessorial_code) {
            $value = array_key_exists($accessorial_code, $params) ? $params[$accessorial_code] : null;
            $this->cacheSelectedAccessorialValue($accessorial_code, $requestCarrierGroup, $requestedCode, $value);
            if ($accessorial_code == 'destination_type') {
                $billingAddress->setDestinationType($value);
                $address->setDestinationType($value);
            } else {
                if ($value === 'true' || $value === 1) {
                    $value = 1;
                } else if($value === 'false') {
                    $value = 0;
                }
                $billingAddress->setData($accessorial_code, (string)$value);
                $address->setData($accessorial_code, (string)$value);
            }
            //SHQ16-2172 - ensure values have been set to 0 and 1
            if(!is_null($value)) {
                $selectedFreightOptions[$accessorial_code] = $value;
            }
        }

        Mage::helper('shipperhq_shipper')->getQuoteStorage()->setSelectedFreightCarrier($selectedFreightOptions);

        //SHQ16-1726 explicitly add selected date if present to request
        if ($requestedDate) {
            $dateSelections = array('carriergroup_id' => $requestCarrierGroup,
                'carrier_code' => $requestedCode,
                'date_selected' => $requestedDate);
            $this->quoteStorage->setSelectedDeliveryArray($dateSelections);
        }
        $this->_cleanDownRatesCollection($requestedCode, $address, $requestCarrierGroup);
        $address->save();
        $billingAddress->save();

        $this->_refreshShippingRates($address);
        Mage::helper('shipperhq_shipper')->getQuoteStorage()->setSelectedFreightCarrier(null);
        if ($requestedDate) {
            $this->quoteStorage->setSelectedDeliveryArray(null);
        }
    }

    protected function _getShippingPrice($quote, $price, $flag, $includeContainer = true)
    {
        return $quote->getStore()->convertPrice(Mage::helper('tax')->
            getShippingPrice($price, $flag, $quote->getShippingAddress()), true, $includeContainer);
    }

    protected function _getCartMapper()
    {
        if(empty($this->_cartMapperInstance)) {
            $this->_cartMapperInstance = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_CartMapper');
        }
        return $this->_cartMapperInstance;
    }


    protected function _cleanDownRatesCollection($carrierCode, $address, $carriergroupId)
    {
        $currentRates = $address->getGroupedAllShippingRates();
        foreach($currentRates as $code => $rates)
        {
            if($code == $carrierCode){
                foreach($rates as $rate) {
                    if($carriergroupId == '' || $rate->getCarriergroupId() == $carriergroupId) {
                        $rate->isDeleted(true);
                    }
                }
            }

        }
    }

    protected function _refreshShippingRates($address)
    {

        if (empty($this->_rates)) {
            if(!$address->getFreeMethodWeight()) {
                $address->setFreeMethodWeight(Mage::getSingleton('checkout/session')->getFreemethodWeight());
            }
            $rateFound = $address->requestShippingRates();
            $address->save();
            $groups = $address->getGroupedAllShippingRates();

            $this->_rates = $groups;
        }

        return $this->_rates;
    }

    /**
     * Returns cache key for some request to accessorials service
     *
     * @param string|array $requestParams
     * @return string
     */
    protected function _getAccessorialsCacheKey($requestParams)
    {
        if (is_array($requestParams)) {
            $workingCopy = $requestParams;
            $itemsString = '';
            if(array_key_exists('items', $workingCopy)) {
                $itemsString = ','.implode(',', array_merge(
                    array_keys($workingCopy['items']),
                    $workingCopy['items']));
                unset($workingCopy['items']);
            }
            $requestParams = implode(',', array_merge(
                    array_keys($workingCopy),
                    $workingCopy)
            ) . $itemsString;
        }
        return crc32($requestParams);
    }

    /**
     * Checks whether some request to accessorials have already been done, so we have cache for it
     *
     * Returns cached response or null
     *
     * @param string|array $requestParams
     * @return null|string
     */
    protected function _getCachedAccessorials($requestParams)
    {
        $key = $this->_getAccessorialsCacheKey($requestParams);
        $lastRequestKey = $this->getQuoteStorage()->getFreightAccesssorialsRequest();
        $result =  $lastRequestKey == $key ? $this->getQuoteStorage()->getFreightAccessorials()
        : null;
        return $result;
    }

    /**
     * Sets received accessorials to cache
     *
     * @param string|array $requestParams
     * @param              $result
     * @param bool         $isCheckout
     *
     * @return $this
     */
    public function setCachedAccessorials($requestParams, $result, $isCheckout = false)
    {
        if($requestParams) {
            $key = $this->_getAccessorialsCacheKey($requestParams);
            $this->getQuoteStorage()->setFreightAccessorialsRequest($key);
        }
        $allAccessorials = $this->getQuoteStorage()->getFreightAccessorials();
        if($isCheckout) {
            foreach($result as $carriergroupId => $cg_accesssorials) {
                foreach($cg_accesssorials as $carrier_code => $cc_accessorials) {
                    $allAccessorials[$carriergroupId][$carrier_code] = $cc_accessorials;
                }
            }
            $this->getQuoteStorage()->setFreightAccessorials($allAccessorials);

        }
        else {
            $this->getQuoteStorage()->setFreightAccessorials($result);

        }

        return $this;
    }

    public function cacheSelectedAccessorialValue($accCode, $carrierGroupId, $carrierCode, $selected)
    {
        $carrierGroupId = $carrierGroupId == '' ? 0 : $carrierGroupId;
        $allAccessorials = $this->getQuoteStorage()->getFreightAccessorials();
        if(array_key_exists($carrierGroupId, $allAccessorials)
            && array_key_exists($carrierCode, $allAccessorials[$carrierGroupId]))
        {
            foreach($allAccessorials[$carrierGroupId][$carrierCode] as $key => $savedAccessorial)
            if($savedAccessorial['code'] == $accCode) {
                $allAccessorials[$carrierGroupId][$carrierCode][$key]['selectedValue'] = $selected;
            }
        }

        $this->getQuoteStorage()->setFreightAccessorials($allAccessorials);
    }

    protected function getQuoteStorage()
    {
        if(!$this->quoteStorage) {
            $this->quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage();
        }
        return $this->quoteStorage;
    }
}