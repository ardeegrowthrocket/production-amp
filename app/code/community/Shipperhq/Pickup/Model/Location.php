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

/**
 * Shipper shipping model
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipper
 */

class Shipperhq_Pickup_Model_Location extends Mage_Core_Model_Abstract
{

    protected static $_debug;
    protected $quote;
    protected $quoteStorage;

    public function getLocationResults($quote, $selectedLocation, $carriergroupId, $carrierCode, $dateSelected, $carrierType, $loadOnly, $addressId, &$resultSet, $isOsc = false)
    {
        self::$_debug = Mage::helper('shipperhq_pickup')->isDebug();
        $this->quote = $quote;
        $this->quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage($this->quote);

        $found = $this->_populateLocationDetails($selectedLocation, $carriergroupId, $carrierCode, $resultSet);

        if ($found) {
            $pickupArr = array( 'carriergroup_id' => $carriergroupId,
                'location' => $selectedLocation,
                'date'  => $dateSelected,
                'carrier_id' => $resultSet['carrier_id']);

            $this->quoteStorage->setPickupArray($pickupArr);
            if(Mage::helper('shipperhq_pickup')->calculateShippingRatesForPickup($carrierType) && $loadOnly == 'false') {

                $this->_cleanDownRatesCollection($carrierCode, $addressId, $carriergroupId);
                $this->_rates = $this->_refreshShippingRates($addressId);

                $this->_populateLocationDetails($selectedLocation, $carriergroupId, $carrierCode, $resultSet);
            } else {
                $this->_rates = $this->_getShippingRates();
            }
            $updatedRates = array();
            foreach ($this->_rates as $code => $rates) {
                if ($code == $carrierCode) {
                    foreach ($rates as $rate) {
                        if(Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($rate->getCarrierType())) {
                            if (!empty($carriergroupId) && $carriergroupId!=$rate['carriergroup_id']) {
                                continue;
                            }
                            $resultSet['price'] = $this->_getShippingPrice($rate->getPrice(),
                                Mage::helper('tax')->displayShippingPriceIncludingTax());
                            if($loadOnly == 'false') {
                                $_excl = $this->_getShippingPrice($rate->getPrice(), Mage::helper('tax')->displayShippingPriceIncludingTax(), false);
                                $_incl = $this->_getShippingPrice($rate->getPrice(), true, false);

                                if($isOsc) {
                                    $label = $this->getMethodTitle( $rate->getMethodTitle(),  $rate->getMethodDescription(), !$isOsc);

                                    $label .= ' '.$this->getOscFormattedPrice($_excl);

                                    if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl)
                                    {
                                        $label .= ' (' .Mage::helper('shipperhq_shipper')->__('Incl. Tax') .' ' .$_incl .')';
                                    }

                                    if($rate['tooltip'] != '') {
                                        $label .= ' '.$this->getOscFormattedTooltip($rate['tooltip']);
                                    }
                                } else {
                                    $label = $_excl;
                                    if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl)
                                    {
                                        $label .= ' (' .Mage::helper('shipperhq_shipper')->__('Incl. Tax') .' ' .$_incl .')';
                                    }
                                    $label .=  ' ' .$this->getMethodTitle( $rate->getMethodTitle(),  $rate->getMethodDescription(), !$isOsc);
                                }

                                $updatedRates[$rate->getCode()] = array(
                                    //  'code' 			=> ,
                                    'price' 				=> $this->_getShippingPrice($rate->getPrice(), Mage::helper('tax')->displayShippingPriceIncludingTax(), false),
                                    //	'method_title' 			=> $rate->getMethodTitle(),
                                    'method_description' 	=> $rate->getMethodTitle(),
                                    'label'                  => $label
                                );
                                if($rate->getErrorMessage()) {
                                    $updatedRates[$rate->getCode()]['error'] = $rate->getErrorMessage();
                                    $this->_getAddress()->setShippingMethod('');
                                    $this->_getAddress()->save();
                                }
                            }
                        }
                    }
                }
            }
            $shipMethodsCarrierGroupsSelect = $this->quoteStorage->getCarriergroupSelected();
            if(count($updatedRates) > 0 ) {
                //verify the rate selected is still in rates, if not update
                if(is_array($shipMethodsCarrierGroupsSelect) && $carriergroupId != '') {
                    reset($updatedRates);
                    $first_key = key($updatedRates);
                    if(!array_key_exists($carriergroupId, $shipMethodsCarrierGroupsSelect)
                        || !array_key_exists($shipMethodsCarrierGroupsSelect[$carriergroupId], $updatedRates)) {

                        $shipMethodsCarrierGroupsSelect[$carriergroupId] = $first_key;
                        $this->quoteStorage->setCarriergroupSelected($shipMethodsCarrierGroupsSelect);
                    }
                }
                $resultSet['updated_rates'] = $updatedRates;
            }
            else {
                $resultSet['updated_rates'] = false;
                if(!$loadOnly && is_array($shipMethodsCarrierGroupsSelect)
                    && array_key_exists($carriergroupId, $shipMethodsCarrierGroupsSelect)) {

                    $selectedMethod = $shipMethodsCarrierGroupsSelect[$carriergroupId];
                    if(strstr($selectedMethod, $carrierCode)) {
                        unset($shipMethodsCarrierGroupsSelect[$carriergroupId]);
                        $this->quoteStorage->setCarriergroupSelected($shipMethodsCarrierGroupsSelect);
                    }
                }
            }
            $this->quoteStorage->setPickupArray(null);
        }
    }

    protected function _getAddress($addressId = false)
    {
        if (is_null($this->_address)) {
            if($addressId) {
                $allShipAddress = $this->quote->getAllShippingAddresses();
                foreach($allShipAddress as $shippingAddress) {
                    if($shippingAddress->getId() == $addressId) {
                        $this->_address = $shippingAddress;
                    }
                }
            }
            if(is_null($this->_address)) {
                $this->_address = $this->quote->getShippingAddress();
            }
        }
        return $this->_address;
    }

    protected function getMethodTitle($methodTitle, $methodDescription, $includeContainer)
    {
        return Mage::helper('shipperhq_shipper')->getMethodTitle($methodTitle, $methodDescription, $includeContainer);
    }

    protected function getOscFormattedPrice($price){
        return Mage::helper('shipperhq_shipper')->getOscFormattedPrice($price);
    }

    protected function getOscFormattedTooltip($tooltip){
        return Mage::helper('shipperhq_shipper')->getOscFormattedTooltip($tooltip);
    }

    protected function _getShippingPrice($price, $flag,  $includeContainer = true)
    {
        return $this->quote->getStore()->convertPrice(Mage::helper('tax')->
            getShippingPrice($price, $flag, $this->_getAddress()), true, $includeContainer);
    }

    protected function _refreshShippingRates($addressId)
    {
        $address = $this->_getAddress($addressId);

        if (empty($this->_rates)) {
            if(!$address->getFreeMethodWeight()) {
                $address->setFreeMethodWeight(Mage::getSingleton('checkout/session')->getFreemethodWeight());
            }
            $address->collectShippingRates()->save();

            $rateFound = $address->requestShippingRates();
            $address->save();
            $groups = $address->getGroupedAllShippingRates();

            $this->_rates = $groups;
        }

        return $this->_rates;
    }


    /**
     * This should be in model really
     */
    protected function _getShippingRates()
    {
        $address = $this->_getAddress();

        if (empty($this->_rates)) {
            $groups = $address->getGroupedAllShippingRates();

            $this->_rates = $groups;
        }

        return $this->_rates;
    }

    protected function _cleanDownRatesCollection($carrierCode, $addressId, $carriergroupId)
    {
        $currentRates = $this->_getAddress($addressId)->getGroupedAllShippingRates();
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
        $addresses = $this->quote->getAllShippingAddresses();
        foreach($addresses as $shippingAddress) {
            $shippingAddress->setIsCheckout(1);
        }
    }

    /**
     * Returns mapping field for location object
     *
     * @return array
     */
    protected function _getLocationMapping()
    {
        return array(
            'method_name' => 'methodName',
            'location_id' => 'pickupId',
            'lat' => 'latitude',
            'long' => 'longitude',
            'showDate' => 'calendarDetails/showDate',
            'map_url' => function ($location) {
                if (!empty($location['locationMap'])) {
                    $rawURL = Mage::getStoreConfig('carriers/shipper/live_url');
                    $scrubbed = $str = preg_replace('#^https?://#', '', $rawURL);
                    $partsOfURL = explode(':', $scrubbed);
                    $url = $partsOfURL[0];
                    return 'http://' .$url. ':3000' .$location['locationMap'];
                }
                return false;
            },
            'street1' => 'street1',
            'street2' => array('street2', ''),
            'city' => 'city',
            'region' => 'state',
            'zip' => 'zipcode',
            'country' => 'country',
            'country_id' => 'country',
            'phone' => 'phone',
            'distance' => 'distance',
            'carrier_id' => 'carrier_id',
            'name' => 'pickupName',
            'email' => array('email', false),
            'fax' =>  array('fax', false),
            'website' =>  array('websiteUrl', false),
            'image_url' =>  array('imageUrl', false),
            'walking_directions' =>  array('walking_directions', false),
            'open_hours' => function ($location) {
                //Opening hours
                if(!empty($location['standardHours'])) {
                    return nl2br($location['standardHours']);
                }
                elseif(!empty($location['openingHours'])) {
                    return sprintf(
                        '%s - %s',
                        date('g:i a', strtotime($location['openingHours'])),
                        date('g:i a', strtotime($location['closingHours']))
                    );
                } elseif (!empty($location['standardHours'])) {
                    return $location['standardHours'];
                }

                return false;
            }
        );
    }

    protected function _populateLocationDetails($locationId, $carriergroupId, $carrierCode, &$resultSet)
    {
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('ShipperHQ Pickup', 'Retrieving location Details', $locationId);
        }

        //default to 0 for carriergroupId
        if($carriergroupId == '') {
            $carriergroupId = 0;
        }

        $location = Mage::helper('shipperhq_pickup')->getLocationDetails($carriergroupId, $carrierCode, $locationId);

        if(!is_array($location)) {
            if (self::$_debug) {
                Mage::helper('wsalogger/log')->postInfo('ShipperHQ Pickup', 'Unable to find location details', $resultSet);
            }
            return false;
        }
        $pickupDisplay = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getPickupDisplayConfig();
        $resultSet = array_merge($pickupDisplay, $resultSet);
        $resultSet['showTime'] = false;
        $resultSet['addressText'] = Mage::helper('shipperhq_shipper')->__('Address') .':';
        $resultSet['hoursText'] =  Mage::helper('shipperhq_shipper')->__('Hours') .':';

        // Map result set of location api to a result set
        $resultSet += Mage::helper('shipperhq_shipper/mapper')->map(
            $this->_getLocationMapping(),
            $location
        );



        $resultSet = $this->_getLocationTimeSlots($location, $resultSet);
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('ShipperHQ Pickup', 'Result Set from Ajax Callback', $resultSet);
        }
        return true;
    }

    protected function _getLocationTimeSlots($location, $resultSet)
    {
        $locationId = $location['pickupId'];
        if(!$location['calendarDetails']['showDate']) {
            return $resultSet;
        }
        $pickupDates = $this->_getAvailablePickupDates($location);

        if (count($pickupDates)>0) {

            $deliveryDatesAndTimes = array();

            if(array_key_exists('timeSlots', $location['calendarDetails']) && !empty($location['calendarDetails']['timeSlots'])) {

                foreach($pickupDates as $dateKey => $date) {
                    $resultSet['date_selected'];
                    if(array_key_exists('date_selected', $resultSet) && $resultSet['date_selected'] != '') {
                        if ($dateKey != $resultSet['date_selected']) continue;

                    }

                    if($slotsFound = Mage::helper('shipperhq_pickup/date')->getLocationTimeSlots($location, $locationId, $dateKey)) {
                        $deliveryDatesAndTimes[$dateKey] = $slotsFound;
                        //Only need to do this once - as time slots are for first date shown/selected
                        break;
                    }
                    else {
                        unset($pickupDates[$dateKey]);
                    }
                }
            }

            if(count($pickupDates) >0 ) {
                $resultSet['pickup_dates']  = $pickupDates;
            }
            else {
                $resultSet['pickup_dates'] = false;
                $resultSet['showDate'] = false;
            }
            if(count($deliveryDatesAndTimes) > 0) {
                $resultSet['time_slots'] = $deliveryDatesAndTimes;
                $resultSet['showTime'] = true;
            }
            else {
                $resultSet['time_slots'] = false;
                $resultSet['showTime'] = false;
            }
        }

        return $resultSet;
    }

    protected function _getAvailablePickupDates($location)
    {
        return Mage::helper('shipperhq_pickup/date')->getDateOptions($location);
    }
}
