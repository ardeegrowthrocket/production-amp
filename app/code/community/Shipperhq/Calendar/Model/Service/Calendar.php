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

class Shipperhq_Calendar_Model_Service_Calendar
{

    public function getCalendarRates($quote, $parameters)
    {

        $carrierCode = $parameters['carrier_code'];
        $carriergroupId = $parameters['carriergroup_id'];
        $dateSelected = $parameters['date_selected'];
        $loadOnly = $parameters['load_only'];
        $osc = array_key_exists('is_osc', $parameters) ? $parameters['is_osc'] == 'true' : false;
        $methodName = $parameters['method_name'];
        $passedInCarriergroupId = $carriergroupId;


        //Multiaddress checkout support
        $addressId = false;
        Mage::helper('shipperhq_shipper')->extractAddressIdAndCarriergroupId($addressId, $carriergroupId);

        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Calendar', 'Get rates for selected date ',
                array('carrierCode' =>$carrierCode, 'carriergroup' => $carriergroupId, 'date selected' =>$dateSelected, 'load only ' => $loadOnly,
                    'method name' => $methodName));
        }
        $resultSet = array();
        $resultSet['date_selected'] = $dateSelected;
        $resultSet['carriergroup_id'] = $carriergroupId;
        $resultSet['carrier_code'] = $carrierCode;

        $calendar = Mage::getModel('shipperhq_calendar/calendar');
        $calendar->getCalendarResults(
            $quote,$carriergroupId, $carrierCode,
            $dateSelected, $loadOnly, $addressId, $resultSet, $osc, $methodName
        );

        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Calendar', 'Returning rates to checkout ',$resultSet);
        }
        $resultSet['carriergroup_id']=$passedInCarriergroupId;

        $inline = Mage::helper('shipperhq_shipper')->useInlineCalendar();

        if($osc && $dateSelected != "" && $inline) {

            $dateSelectedArray = Mage::helper('shipperhq_shipper')->getQuoteStorage($quote)->getDeliveryDateArray();

            if ($dateSelectedArray == null) {
                $dateSelectedArray = array();
            }

            $key = $carrierCode.$carriergroupId;

            $dateSelectedArray[$key] = $dateSelected;

            Mage::helper('shipperhq_shipper')
                ->getQuoteStorage($quote)->setDeliveryDateArray($dateSelectedArray);
        }

        //This is used with/for PVR
        Mage::helper('shipperhq_shipper')
            ->getQuoteStorage($quote)
            ->setSelectedDeliveryArray(null);

        return $resultSet;
    }

    public function getCalendarDatesOnly($parameters)
    {
        $carrierCode = $parameters['carrier_code'];
        $carriergroupId = $parameters['carriergroup_id'];
        $dateSelected = $parameters['date_selected'];
        $loadOnly = $parameters['load_only'];
        $passedInCarriergroupId = $carriergroupId;

        //Multiaddress checkout support
        $addressId = false;
        Mage::helper('shipperhq_shipper')->extractAddressIdAndCarriergroupId($addressId, $carriergroupId);

        $allCalendarDetails = Mage::getSingleton('core/session')->getAllCalendarDetails();
        $calendarDetails = false;
        if(is_array($allCalendarDetails)) {
            if(array_key_exists($carriergroupId, $allCalendarDetails) && array_key_exists($carrierCode, $allCalendarDetails[$carriergroupId])) {
                if(!empty($allCalendarDetails[$carriergroupId][$carrierCode])) {
                    $calendarDetails = $allCalendarDetails[$carriergroupId][$carrierCode];
                }
            }
        }

        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Calendar', 'Get possible dates ',
                array('carrierCode' =>$carrierCode, 'carriergroup' => $carriergroupId, 'date selected' =>$dateSelected, 'load only ' => $loadOnly));
        }
        $resultSet = array();
        $resultSet['date_selected'] = $dateSelected != '' ? $dateSelected : $calendarDetails['date_on_rate'];
        $resultSet['carriergroup_id'] = $carriergroupId;
        $resultSet['carrier_code'] = $carrierCode;

        $calendar = Mage::getModel('shipperhq_calendar/calendar');
        $calendar->getCalendarDatesOnly(
            $calendarDetails, $resultSet
        );

        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Calendar', 'Returning calendar details to catalog page ',$resultSet);
        }
        $resultSet['carriergroup_id']=$passedInCarriergroupId;

        return $resultSet;
    }

    public function setEstimateDeliveryDates($parameters)
    {
        $carrierCode = $parameters['carrier_code'];
        $carriergroupId = $parameters['carriergroup_id'];
        $dateSelected = $parameters['date_selected'];

        $selectedDelivery = array('carriergroup_id' => $carriergroupId,
            'carrier_code' => $carrierCode,
            'date_selected' => $dateSelected);
        Mage::getSingleton('core/session')->setSelectedDeliveryArray($selectedDelivery);
        $resultSet['date_selected'] = $dateSelected;
        $resultSet['carriergroup_id'] = $carriergroupId;
        $resultSet['carrier_code'] = $carrierCode;
        return $resultSet;
    }
}