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

class Shipperhq_Calendar_Helper_Date {


    protected static $_blackoutPickupDates;
    protected static $_blackoutPickupDays;
    protected static $_minLeadTime;
    protected static $_minLeadTimeUnit;
    protected static $_maxLeadTime;
    protected static $_numDisplayDays;

    /**
     * Returns back an array of dates for Store Pickup
     * These dates are not influenced by the location
     * @return array
     */
    public function getDateOptions($calendarDetails)
    {
        $numPickupDays = array_key_exists('maxDays', $calendarDetails) ? $calendarDetails['maxDays'] : 10;

        //Convert java linux timestamps (milliseconds) into php linux timestamps (seconds)
        $dateOptions = array();
        $dateFormat = Mage::helper('shipperhq_shipper')->getDateFormat();
        $zendDateFormat = Mage::helper('shipperhq_shipper')->getZendDateFormat();
        $startDate = $calendarDetails['start'];
        $endDate = isset($calendarDetails['calendarEndDate']) ?
            Mage::app()->getLocale()->date($calendarDetails['calendarEndDate']/1000, null, null, true)->getTimestamp()
            : false;

        $arrBlackoutDates = array();
        foreach($calendarDetails['blackoutDates'] as $blackoutDate)
        {
            $arrBlackoutDates[] = date($dateFormat, strtotime($blackoutDate));
        };
        $arrBlackoutDays = array();

        foreach($calendarDetails['blackoutDays'] as $dayOfWeek)
        {
            //Java Sunday = 7, Monday = 1. PHP Monday = 1, Saturday = 6, Sunday = 0
            if($dayOfWeek == 7) {
                $dayOfWeek = 0;
            }
            $arrBlackoutDays[] = $dayOfWeek;
        }

        if(count($arrBlackoutDays) == 7 ) {
            if(Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq Calendar', 'No date options available ', 'All days of week are set as black out days for carrier');
            }
            return $dateOptions;
        }

        $localDT = Mage::app()->getLocale()->date(now(), null, null, true)->toString('r');

        Mage::helper('wsalogger/log')->postInfo('Shipperhq Calendar',
                                                 'Current Date & Time According to Magento Time Zone',
                                                 $localDT);
        while(count($dateOptions) < $numPickupDays) {
            //SHQ16-1566 support end date inclusive
            if($endDate && $startDate > $endDate) {
                break;
            }
            $nextDay = Mage::app()->getLocale()->date($startDate, null, null, true)->toString($zendDateFormat);

            // Blackout day or date...get next available
            if(in_array($nextDay, $arrBlackoutDates) ||
                in_array(Mage::app()->getLocale()->date($startDate, null, null, true)->toString('e'), $arrBlackoutDays)) {
                $this->_addDay($startDate);
                continue;
            }
            $dateOptions[$nextDay] = $nextDay;
            $this->_addDay($startDate);
        }
        return $dateOptions;

    }

    /**
     * Get a numerical representation of the day of the week from a date
     *
     * @param string $date
     * @return bool|string
     */
    public function getDayOfWeek($date){
        $unixTime = strtotime($date);

        $dayOfWeek = date('N', $unixTime);

        return $dayOfWeek;
    }


    public function getNumberPickupDays()
    {
        $locationDisplayOptions = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getPickupDisplayConfig();
        if(array_key_exists('max_pickupdays', $locationDisplayOptions) && $locationDisplayOptions['max_pickupdays'] != '') {
            return (int)$locationDisplayOptions['max_pickupdays'];
        }
        return 10;
    }

    /**
     * Given a date will add a day to it.
     * @param $day
     * @param int $numDaysToAdd
     */
    protected function _addDay(&$day,$numDaysToAdd = 1) {
        $day = strtotime('+' .$numDaysToAdd .' day', $day);
        return $day;

    }

    public function getDeliveryTimeSlots($calendarDetails, $date)
    {

        if(!array_key_exists('timeSlots', $calendarDetails))
        {
            return false;
        }
        $today = date('m/d/Y');
        $isToday = false;
        if($today == $date) {
            $isToday = true;
        }


        $timeSlotDetail = (array)$calendarDetails['timeSlots'];
        $sortTime = array();
        foreach ($timeSlotDetail as $key => $val) {
            $values = (array)$val;
            $sortTime[$key] = $values['timeStart'];
            $timeSlotDetail[$key] = $values;
        }

        array_multisort($sortTime, SORT_ASC, $timeSlotDetail);

        //for implementation of date/day based slot detail in future
        $timeSlots = array();
        foreach($timeSlotDetail as $timeSlotDetail) {
            $start = $timeSlotDetail['timeStart'];
            $end =  $timeSlotDetail['timeEnd'];
            $interval = $timeSlotDetail['interval'];


            $startTime = strtotime($start);
            $endTime = strtotime($end);

            if(!$startTime || !$endTime) {
                continue;
            }

            $currentTime = 0;
            //if we are generating slots for today, make sure we don't offer any in the past
            //and we account for lead time in hours
            if($isToday) {
                $currentTime = Mage::app()->getLocale()->storeTimeStamp();
            }

            //if interval is half or full day then calculate those intervals
            if($interval <= 2) {
                $interval = (($endTime - $startTime)/60)/$interval;
            }
            $intStartTime = $startTime;
            $intEndTime = $startTime;
            $intervalString = '+' . $interval . ' minutes';

            while ($endTime > $intStartTime) {
                $intEndTime = strtotime($intervalString, $intStartTime);
                if ($intEndTime > $endTime) {
                    $intEndTime = $endTime;
                }
                //will ignore any time slots in the past
                if($intStartTime > $currentTime) {
                    $timeSlots[date('H:i:s', $intStartTime) . '_' . date('H:i:s', $intEndTime)] = date('g:i a', $intStartTime) . ' - ' . date('g:i a', $intEndTime);
                }
                $intStartTime = $intEndTime;

            }
        }

        if(count($timeSlots) == 0) {
            return false;
        }
        return $timeSlots;

    }

}