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

include_once 'ShipperHQ/WS/Client/WebServiceClient.php';
include_once 'ShipperHQ/WS/Response/ErrorMessages.php';

class Shipperhq_Calendar_Model_Carrier_Calendar
    extends Shipperhq_Shipper_Model_Carrier_Shipper
    implements Mage_Shipping_Model_Carrier_Interface
{

    /**
     * Flag for check carriers for activity
     *
     * @var string
     */
    protected $_activeFlag = 'active';

    /**
     * Identifies this shipping carrier
     * @var string
     */
    protected $_code = 'calendar';

    /**
     * Error Message Lookup Object
     *
     */
    protected $_errorMessageLookup = null;

    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

    /**
     * Code for Wsalogger to pickup
     *
     * @var string
     */
    protected $_modName = 'Shipperhq_Calendar';


    public function extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit)
    {
        $calendarCarrierGroupId = $isSplit ? $carrierGroupId : 0;

        $carrierResultWithRates = array(
            'code'  => $carrierRate->carrierCode,
            'title' => $carrierRate->carrierTitle);

        if(isset($carrierRate->error)) {
            $carrierResultWithRates['error'] = (array)$carrierRate->error;
            $carrierResultWithRates['carriergroup_detail']['carriergroup_id'] = $carrierGroupId;
            $carrierResultWithRates['carriergroup_detail']['carrierGroupId'] = $carrierGroupId;
        }
        $startDate = '';
        $carrierCode = $carrierRate->carrierCode;
        $carrierId = $carrierRate->carrierId;
        $calendarDetails = (array)$carrierRate->calendarDetails;

        if(isset($carrierRate->rates)) {
            $thisCarriersRates = $this->populateRates($carrierRate, $carrierGroupDetail, $carrierGroupId);

            $carrierResultWithRates['rates'] = $thisCarriersRates;
            foreach($carrierRate->rates as $oneRate) {
                $startDate =  Mage::app()->getLocale()->date($oneRate->deliveryDate/1000, null, null, true)->getTimestamp();
            }
        }
        if(!empty($calendarDetails)) {
            if($calendarDetails['startDate'] != '') {
                $calendarDetails['start'] = Mage::app()->getLocale()->date($calendarDetails['startDate']/1000, null, null, true)->getTimestamp();
            }
            else {
                $calendarDetails['start'] = $startDate;
            }
            $calendarDetails['date_on_rate'] = date(Mage::helper('shipperhq_shipper')->getDateFormat(),$startDate);
            $calendarDetails['carrier_id'] = $carrierId;
            $allCalendarDetails = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getCalendarDetails();
            $allCalendarDetails[$calendarCarrierGroupId][$carrierCode] = $calendarDetails;
            $allCalendarDetails[$carrierGroupId][$carrierCode] = $calendarDetails;

            if($calendarCarrierGroupId == 0 && array_key_exists($carrierGroupId, $allCalendarDetails)) {
                $allCalendarDetails[$carrierGroupId][$carrierCode] = $calendarDetails;
            }

            Mage::helper('shipperhq_shipper')->getQuoteStorage()->setCalendarDetails($allCalendarDetails);
            Mage::getSingleton('catalog/session')->setCalendarDetails($allCalendarDetails);
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('ShipperHQ Calendar', 'Calendar details for ' .$carrierCode,
                    $calendarDetails);
            }
        }



        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('ShipperHQ Calendar',
                $carrierCode. ' with Calendar enabled returning carrier results',
                $carrierResultWithRates);
        }
        return $carrierResultWithRates;
    }


    public function isTrackingAvailable()
    {
        return false;
    }

}
