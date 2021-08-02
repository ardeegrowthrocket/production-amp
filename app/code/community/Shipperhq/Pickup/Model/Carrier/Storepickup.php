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

class Shipperhq_Pickup_Model_Carrier_Storepickup
    extends Shipperhq_Shipper_Model_Carrier_Shipper
    implements Mage_Shipping_Model_Carrier_Interface
{

    /*
    * Identifies store pickup carrier types
    */
    const PICKUP_CARRIER_TYPE = 'pickup';
    const PICKUP_ADDRESS_NAME = 'In Store Pickup';

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
    protected $_code = 'pickup';

    /*
     * Other carrier codes that are handled as pickup carriers
     */
    protected $_accessPointCode = 'accessPoint';

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
    protected $_modName = 'Shipperhq_Pickup';


    public function extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit)
    {
        $locationCarrierGroupId = $isSplit ? $carrierGroupId : 0;
        $carrierResultWithRates = array(
            'code'  => $carrierRate->carrierCode,
            'title' => $carrierRate->carrierTitle,
            //freight_quote_id
        );
        $carrierId = $carrierRate->carrierId;
        if(isset($carrierRate->error)) {
            $carrierResultWithRates['error'] = (array)$carrierRate->error;
            $carrierResultWithRates['carriergroup_detail']['carriergroup_id'] = $carrierGroupId;
            $carrierResultWithRates['carriergroup_detail']['carrierGroupId'] = $carrierGroupId;
        }
        $methodName = '';
        if(isset($carrierRate->rates)) {
            $thisCarriersRates = $this->populateRates($carrierRate, $carrierGroupDetail, $carrierGroupId);
            foreach($carrierRate->rates as $oneRate) {
                $methodName = $oneRate->name;
            }
        }

        $quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage();
        $closestLocationName = false;

        if(isset($carrierRate->pickupLocationDetails)
            && isset($carrierRate->pickupLocationDetails->pickupLocations)) {
            $locationsAvailable = array();
            //not used at present
            //$defaultCalendarDetails = (array)$carrierRate->calendarDetails;
            foreach($carrierRate->pickupLocationDetails->pickupLocations as $location) {
                $locationAsArray =(array)$location;
                $calendarDetails = (array)$location->calendarDetails;

                if(!empty($calendarDetails)) {
                	if ($calendarDetails['startDate'] != '') {
						$calendarDetails['start'] = $calendarDetails['startDate']/1000;
					} else {
						$pickupDateUTC = $location->pickupDate/1000;

						$calendarDetails['start'] = $pickupDateUTC;
						Mage::helper('wsalogger/log')->postDebug('ShipperHQ Pickup',
							'Setting Calendar and Time Slot Start to pickupDate',
							"Pickup UTC: ".$pickupDateUTC.
							"<br />Pickup Start: ".$location->pickupDateStr
						);
					}
                } else {
                    $calendarDetails['showDate'] = false;
                }

                $locationAsArray['calendarDetails'] = $calendarDetails;
                $locationAsArray['methodName'] = $methodName;
                $locationAsArray['carrier_id'] = $carrierId;
                $locationsAvailable[$location->pickupId] = $locationAsArray;
                if(!$closestLocationName) {
                    $closestLocationName = $location->pickupName;
                }
            }

            if (count($locationsAvailable) > 0 ) {
                $locations = $quoteStorage->getClosestLocations();
                //SHIPPERHQ-531 save against both carriergroups for re-request
                $locations[$locationCarrierGroupId][$carrierRate->carrierCode] = $locationsAvailable;
                isset($carrierGroupDetail['carrierGroupId'])? $locations[$carrierGroupDetail['carrierGroupId']][$carrierRate->carrierCode] = $locationsAvailable : null;
                $quoteStorage->setClosestLocations($locations);
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('ShipperHQ Pickup', 'Store Pickup closest locations',
                        $locationsAvailable);
                }
                if(isset($carrierRate->pickupLocationDetails->pickupCart)
                    && (string)$carrierRate->pickupLocationDetails->pickupCart == '1'
                    && !Mage::helper('shipperhq_shipper')->isCheckout()) {
                    foreach($thisCarriersRates as $key => $rate) {
                        $rate['method_title'].= ': ' .$closestLocationName;
                        $thisCarriersRates[$key] = $rate;
                    }
                }
            }
            else {
                $carrierResultWithRates['error'] = array('errorCode' =>  '2000',
                    'description' => Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('error', 2000));
            }
            if(count($thisCarriersRates) > 0) {
                $carrierResultWithRates['rates'] = $thisCarriersRates;
            }
            $pickupDisplayConfig = array();
            $openHours = false;
            $showMap = "hidden";
            $googleApiKey = '';
            $showAddress = 'hidden';
            if(isset($carrierRate->pickupLocationDetails->showOpeningHours)
                && $carrierRate->pickupLocationDetails->showOpeningHours == 1) {
                $openHours = true;
            }
            $pickupDisplayConfig['show_open_hours'] = $openHours;

            if(isset($carrierRate->pickupLocationDetails->showMap)) {
                $showMap = (string)$carrierRate->pickupLocationDetails->showMap;
            }
            $pickupDisplayConfig['show_map'] = $showMap;

            if(isset($carrierRate->pickupLocationDetails->googleApiKey)
                && (string)$carrierRate->pickupLocationDetails->googleApiKey != '') {
                    $googleApiKey = (string)$carrierRate->pickupLocationDetails->googleApiKey;
            }
            $pickupDisplayConfig['google_api_key'] = $googleApiKey;

            if(isset($carrierRate->pickupLocationDetails->showAddress)
                && (string)$carrierRate->pickupLocationDetails->showAddress != '') {
                $showAddress = (string)$carrierRate->pickupLocationDetails->showAddress;
            }
            $pickupDisplayConfig['show_address'] = $showAddress;

            $quoteStorage->setPickupDisplayConfig($pickupDisplayConfig);
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('ShipperHQ Pickup', 'Store Pickup display configuration data',
                    $pickupDisplayConfig);
            }
        }

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('ShipperHQ Pickup', 'Store Pickup returning carrier results',
                $carrierResultWithRates);
        }
        return $carrierResultWithRates;
    }

    public function isTrackingAvailable()
    {
        return false;
    }

    public function getAllPickupCarrierCodes()
    {
        $codes = array($this->_accessPointCode, $this->getCarrierCode());
        return $codes;
    }

    public function getAccessPointCarrierCode()
    {
        return $this->_accessPointCode;
    }

}
