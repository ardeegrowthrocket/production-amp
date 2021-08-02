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
 * Shipping data helper
 */
class Shipperhq_Calendar_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected static $_debug;

    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isDebug()
    {
        if (self::$_debug == NULL) {
            self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Calendar');
        }
        return self::$_debug;
    }

    /*
     *
     */
    public function getOurCarrierCode()
    {
        return Mage::getModel('shipperhq_calendar/carrier_calendar')->getCarrierCode();
    }

    /*
     * Determine if carrier in carriergroup is enabled to display calendar
     */
    public function hasCalendarDetails($carriergroupId, $code, $carrierType)
    {
        if($carrierType ==  'pickup') return false;
        if(strstr($carriergroupId, 'ma')) {
            if(strstr($carriergroupId, 'ZZ')) {
                $parts = explode('ZZ', $carriergroupId);
                $carriergroupId = $parts[1];
            }
            else {
                $carriergroupId = '';
            }
        }
        if($carriergroupId == '') $carriergroupId = 0;
        $allCalendarDetails = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getCalendarDetails();
        Mage::getSingleton('core/session')->setAllCalendarDetails($allCalendarDetails);
        if(is_array($allCalendarDetails)) {
            if(array_key_exists($carriergroupId, $allCalendarDetails) && array_key_exists($code, $allCalendarDetails[$carriergroupId])) {
                if(!empty($allCalendarDetails[$carriergroupId][$code])) {
                    return true;
                }
            }
        }
        return false;
    }

    /*
     * Determine if carrier in carriergroup is enabled to display calendar
     */
    public function getCalendarDetails($carriergroupId, $code, $carrierType)
    {
        if($carrierType ==  'pickup') return false;
        if(strstr($carriergroupId, 'ma')) {
            if(strstr($carriergroupId, 'ZZ')) {
                $parts = explode('ZZ', $carriergroupId);
                $carriergroupId = $parts[1];
            }
            else {
                $carriergroupId = '';
            }
        }
        if($carriergroupId == '') $carriergroupId = 0;
        $allCalendarDetails = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getCalendarDetails();
        if(is_array($allCalendarDetails)) {
            if(array_key_exists($carriergroupId, $allCalendarDetails) && array_key_exists($code, $allCalendarDetails[$carriergroupId])) {
                if(!empty($allCalendarDetails[$carriergroupId][$code])) {
                    return $allCalendarDetails[$carriergroupId][$code];
                }
            }
        }
        return false;
    }

    public function addSelectedDatesToRequest(&$request)
    {
        if ($selectedDeliveryDate = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getSelectedDeliveryArray()){
            if(array_key_exists('date_selected', $selectedDeliveryDate)) {
               // date_default_timezone_set('UTC');
                $timeStamp = Mage::app()->getLocale()->date($selectedDeliveryDate['date_selected'], null, null, true)->toString('U');
                $request->setDeliveryDateSelected($timeStamp);
                $request->setDeliveryDate($selectedDeliveryDate['date_selected']);
                $request->setCarriergroupId($selectedDeliveryDate['carriergroup_id']);
            }
            if(array_key_exists('carrier_id', $selectedDeliveryDate)) {
                $request->setCarrierId($selectedDeliveryDate['carrier_id']);
                $request->setCarriergroupId($selectedDeliveryDate['carriergroup_id']);
            }
        }
        Mage::dispatchEvent('shipperhq_calendar_add_selected_dates_to_request', array('request' => $request));


    }

    public function cleanUpCalendarsInSession($carrierCode, $carriergroupId, $isSplit = true)
    {
        $calendarCarrierGroupId = $isSplit ? $carriergroupId : 0;

        if ($selectedDeliveryDate = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getSelectedDeliveryArray()){
            if(array_key_exists('date_selected', $selectedDeliveryDate)) {
                return;
            }
        }

        $allCalendarDetails = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getCalendarDetails();
        if(is_array($allCalendarDetails)) {
            if(array_key_exists($calendarCarrierGroupId, $allCalendarDetails) && array_key_exists($carrierCode, $allCalendarDetails[$calendarCarrierGroupId])) {
                unset($allCalendarDetails[$calendarCarrierGroupId][$carrierCode]);
                Mage::helper('shipperhq_shipper')->getQuoteStorage()->setCalendarDetails($allCalendarDetails);
            }
        }

    }

}
