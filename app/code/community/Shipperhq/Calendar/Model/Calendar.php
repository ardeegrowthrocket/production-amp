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

class Shipperhq_Calendar_Model_Calendar extends Mage_Core_Model_Abstract
{
    protected $quote;
    protected $quoteStorage;
    protected $_rates;
    protected $_address;
    protected static $_debug;

    public function getCalendarResults($quote, $carriergroupId, $carrierCode, $dateSelected, $loadOnly, $addressId, &$resultSet, $isOsc = false, $methodName)
    {
        $this->quote = $quote;
        $this->quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage($quote);

        $ourCalendarDetails = $this->_getOurCalendarDetails($carriergroupId, $carrierCode);
        $carrierID = $ourCalendarDetails ? $ourCalendarDetails['carrier_id'] : '';

        $selectedDelivery = array('carriergroup_id' => $carriergroupId,
            'carrier_code' => $carrierCode,
            'carrier_id'    => $carrierID,
            'date_selected' => $dateSelected);
        $this->quoteStorage->setSelectedDeliveryArray($selectedDelivery);

        if($loadOnly == 'true') {
            $this->_rates = $this->_getShippingRates($addressId);
        }
        else {
            $this->_cleanDownRatesCollection($carrierCode, $addressId, $carriergroupId);
            $this->_rates = $this->_refreshShippingRates($addressId);
            $ourCalendarDetails = $this->_getOurCalendarDetails($carriergroupId, $carrierCode);
        }

        if($ourCalendarDetails) {
            $resultSet['delivery_dates'] = false;
            $resultSet['show_deldate'] = $ourCalendarDetails['showDate'];
            $resultSet['show_deltime'] = false;
            $resultSet['time_slots'] = false;
            $this->_getAvailableDeliveryDatesAndTimes($ourCalendarDetails, $resultSet);
        }

        $updatedRates = array();
        $dateOnRate = '';
        $dateFormat = Mage::helper('shipperhq_shipper')->getDateFormat();
        $tooltipImgUrl = Mage::getModel ('core/design_package')->getSkinUrl('images/shipperhq/tooltip.jpg');
        $carrierType = false;
        foreach ($this->_rates as $code => $rates) {
            if ($code == $carrierCode) {
                foreach ($rates as $rate) {
                    $carrierType = $rate->getCarrierType();
                    if($carriergroupId != '' && $rate->getCarriergroupId() != $carriergroupId) {
                        continue;
                    }
                    if($methodName == $rate['code'] || $methodName == '') {
                        $dateOnRate = date($dateFormat, strtotime($rate->getDeliveryDate()));
                    }
                    if($dateSelected != '' && Date('Y-m-d', (strtotime($dateSelected))) != Date('Y-m-d', strtotime($rate->getDeliveryDate())) ){
                        if(self::$_debug) {
                            Mage::helper('wsalogger/log')->postCritical('Shipperhq Calendar',
                                'Delivery date in rates does not match selected date ',
                                array('Rate ' =>$rate->getCode(),
                                    'rate delivery date'=> $rate->getDeliveryDate(),
                                    'date selected' =>$dateSelected));
                        }
                    }

                    $_excl = $this->_getShippingPrice($rate->getPrice(), Mage::helper('tax')->displayShippingPriceIncludingTax(), $isOsc);
                    $_incl = $this->_getShippingPrice($rate->getPrice(), true, !$isOsc);

                    $label =  $this->getMethodTitle($rate->getMethodTitle(), $rate->getMethodDescription(), !$isOsc);

                    $label .= ' <strong><span class="price">'.$_excl.'</span></strong>'; //Should be <shipping_description> <shipping_price> SHQ16-2360

                    if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl)
                    {
                        $label .= ' (' .Mage::helper('shipperhq_shipper')->__('Incl. Tax') .' ' .$_incl .')';
                    }

                    if ($rate->getTooltip()) {
                        $label .= '<span style="float:right;" class="helpcursor" title="'.$rate->getTooltip().'">
                                       <img src="'.$tooltipImgUrl.'">
                                   </span>';
                    }
                    $updatedRates[$rate->getCode()] = array(
                        'price' 				=> $this->_getShippingPrice($rate->getPrice(), Mage::helper('tax')->displayShippingPriceIncludingTax(), false),
                        'method_description' 	=> $rate->getMethodTitle(),
                        'label'                 => $label,
                        'tooltip'               => $rate->getTooltip(),
                    );

                    if($rate->getErrorMessage()) {
                        $updatedRates[$rate->getCode()]['error'] = $rate->getErrorMessage();
                        $this->_getAddress()->setShippingMethod('');
                        $this->_getAddress()->save();
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
            if(is_array($shipMethodsCarrierGroupsSelect)
                && array_key_exists($carriergroupId, $shipMethodsCarrierGroupsSelect)) {

                $selectedMethod = $shipMethodsCarrierGroupsSelect[$carriergroupId];
                if(strstr($selectedMethod, $carrierCode)) {
                    unset($shipMethodsCarrierGroupsSelect[$carriergroupId]);
                    $this->quoteStorage->setCarriergroupSelected($shipMethodsCarrierGroupsSelect);
                }
            }
        }
        //SHQ16-1357 - don't show default date if it's a custom rate carrier
        if($dateSelected != '') {
            $defaultDate = $dateSelected;
        }
        elseif($carrierType && $carrierType != 'custom') {
            $defaultDate = $dateOnRate;
        }
        else {
            $defaultDate = '';
        }
        $resultSet['default_date'] = $defaultDate;
        $this->quoteStorage->setSelectedDeliveryArray(null);

    }

    public function getCalendarDatesOnly($calendarDetails, &$resultSet)
    {
        if($calendarDetails) {
            $resultSet['delivery_dates'] = false;
            $resultSet['show_deldate'] = $calendarDetails['showDate'];
            $resultSet['show_deltime'] = false;
            $resultSet['time_slots'] = false;
            $this->_getAvailableDeliveryDatesAndTimes($calendarDetails, $resultSet);
        }

        $selectedDeliveryDate = Mage::getSingleton('core/session')->getSelectedDeliveryArray();
        if($selectedDeliveryDate) {
            if(array_key_exists('date_selected', $selectedDeliveryDate)) {
                $resultSet['date_selected'] = $selectedDeliveryDate['date_selected'];
            }
        }
    }

    protected function _getOurCalendarDetails($carriergroupId, $carrierCode)
    {
        $allCalendarDetails = $this->quoteStorage->getCalendarDetails();
        if($carriergroupId ==  '') $carriergroupId = 0;
        if(is_array($allCalendarDetails) && array_key_exists($carriergroupId, $allCalendarDetails) && array_key_exists($carrierCode, $allCalendarDetails[$carriergroupId])) {
            $ourCalendarDetails = $allCalendarDetails[$carriergroupId][$carrierCode];
            return $ourCalendarDetails;
        }
        return false;

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

    protected function _getAvailableDeliveryDatesAndTimes($calendarDetails, &$resultSet)
    {
        if(!$calendarDetails['showDate']) {
            return true;
        }
        $deliveryDates = Mage::helper('shipperhq_calendar/date')->getDateOptions($calendarDetails);

        if (count($deliveryDates)>0) {
            $deliveryDatesAndTimes = array();
            if(array_key_exists('timeSlots', $calendarDetails) && !empty($calendarDetails['timeSlots'])) {
                foreach($deliveryDates as $dateKey => $date) {
                    if(array_key_exists('date_selected', $resultSet) && $resultSet['date_selected'] != '') {
                        if ($dateKey != $resultSet['date_selected']) continue;

                    }
                    if($slotsFound = Mage::helper('shipperhq_calendar/date')->getDeliveryTimeSlots($calendarDetails, $dateKey)) {
                        $deliveryDatesAndTimes[$dateKey] = $slotsFound;
                        //Only need to do this once - as time slots are for first date shown/selected
                        break;
                    }
                    else {
                        unset($deliveryDates[$dateKey]);
                    }
                }
            }

            if(count($deliveryDates) >0 ) {
                $resultSet['delivery_dates']  = $deliveryDates;
            }
            else {
                $resultSet['delivery_dates'] = false;
                $resultSet['show_deldate'] = false;
                $resultSet['show_deltime'] = false;
            }
            if(count($deliveryDatesAndTimes) > 0) {
                $resultSet['time_slots'] = $deliveryDatesAndTimes;
                $resultSet['show_deltime'] = true;
            }
            else {
                $resultSet['time_slots'] = false;
                $resultSet['show_deltime'] = false;
            }
        }
        return true;
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
    protected function _getShippingRates($addressId)
    {
        $address = $this->_getAddress($addressId);
        if (empty($this->_rates)) {
            $groups = $address->getGroupedAllShippingRates();
            $this->_rates = $groups;
        }

        return $this->_rates;
    }

}
