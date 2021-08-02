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
class Shipperhq_Pickup_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected static $_debug;
    protected static $_preselectPickupAddressId = '9999';

    protected $_pickupCarrierCodes;

    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isDebug()
    {
        if (self::$_debug == NULL) {
            self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Pickup');
        }
        return self::$_debug;
    }

    /*
     *
     */
    public function getOurCarrierCode()
    {
        return Mage::getModel('shipperhq_pickup/carrier_storepickup')->getCarrierCode();
    }

    public function isPickupEnabledCarrier($carrierType)
    {
        if(is_null($this->_pickupCarrierCodes)) {
            $this->_pickupCarrierCodes = Mage::getModel('shipperhq_pickup/carrier_storepickup')->getAllPickupCarrierCodes();
        }

        return in_array($carrierType, $this->_pickupCarrierCodes);
    }

    public function isUpsAccessPointCarrier($carrierType)
    {
        return $carrierType == Mage::getModel('shipperhq_pickup/carrier_storepickup')->getAccessPointCarrierCode();
    }

    public function calculateShippingRatesForPickup($carrierType)
    {
        if($this->isUpsAccessPointCarrier($carrierType)) {
            return true;
        }
        //could have static and dynamic pickup carriers - save on globals?
        return true;
    }

    public function addPickupToRequest($request)
    {
        $storage = Mage::helper('shipperhq_shipper')->getQuoteStorage($request->getQuote());
        if ($storage && ($pickupArray = $storage->getPickupArray())) {
            if(array_key_exists('date', $pickupArray)) {
                date_default_timezone_set('UTC');
                $request->setDeliveryDateSelected(strtotime($pickupArray['date']));
                $request->setDeliveryDate($pickupArray['date']);
            }
            if(array_key_exists('location', $pickupArray)) {
                $request->setLocationSelected($pickupArray['location']);
            }
            if(array_key_exists('carrier_id', $pickupArray)) {
                $request->setCarrierId($pickupArray['carrier_id']);
            }

            if(array_key_exists('carriergroup_id', $pickupArray)) {
                $request->setCarriergroupId($pickupArray['carriergroup_id']);
            }
        }

    }

    /*
     * Retrieve location details from session
     */
    public function getLocationDetails($carriergroupId, $carrierCode, $locationId)
    {
        $locations = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getClosestLocations();
        if(!is_array($locations) || !array_key_exists($carriergroupId, $locations)
            || !array_key_exists($carrierCode, $locations[$carriergroupId])
            || !array_key_exists($locationId, $locations[$carriergroupId][$carrierCode])) {
            return false;
        }
        $pickupLocation = $locations[$carriergroupId][$carrierCode][$locationId];
        if(is_null($pickupLocation) || $pickupLocation['pickupId'] != $locationId) {
            $pickupLocation = null;
            return false;
        }
        return $pickupLocation;
    }

    public function getPreviewTemplate()
    {
        $showPickupConfirm = Mage::helper('shipperhq_shipper')->showPickupConfirm();

        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup') && $showPickupConfirm) {
            return 'shipperhq/checkout/onestepcheckout/pickup_review.phtml';
        }
        return 'onestepcheckout/preview/preview.phtml';
    }

    public function preselectPickupEnabled()
    {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if(!is_null($globals) && array_key_exists('showPickupMulti', $globals)
            && $globals['showPickupMulti'] != '') {
            return $globals['showPickupMulti'];
        }
         return false;

    }

    public function pickupPreselected($request)
    {
        $isPreselected = false;
        foreach($request->getAllItems() as $item)
        {
            $quoteAddress = $item->getAddress();

            if(isset($quoteAddress)) {
                $isPreselected = $quoteAddress->getPickupPreselected();
            }
        }
        return $isPreselected;
    }

    public function getPreselectPickupAddressId()
    {
        return self::$_preselectPickupAddressId;
    }

    public function setPickupChosenOnCarriergroupItems($cgDetails, $shippingAddress)
    {
        $itemsGrouped = Mage::helper('shipperhq_shipper')->getItemsGroupedByCarrierGroup($shippingAddress->getAllItems());
        foreach($cgDetails as $carrierGroupDetail)
        {
            if(array_key_exists('location_id', $carrierGroupDetail)) {
                $carrierGroupId = $carrierGroupDetail['carrierGroupId'];
                $pickupLocation = $this->getLocationDetails($carrierGroupId,
                       $carrierGroupDetail['carrier_code'], $carrierGroupDetail['location_id']);
                if($pickupLocation) {
                    $pickupText = $carrierGroupDetail['carrierTitle'] . ' Location: '
                        .$pickupLocation['pickupName']. ' ' .$carrierGroupDetail['pickup_date'] ;
                    if(array_key_exists('pickup_slot', $carrierGroupDetail)) {
                        $pickupSlot = str_replace('_', ' - ', $carrierGroupDetail['pickup_slot']);
                        $slotArray = explode(' - ', $pickupSlot);
                        $pickupText .= ' ' .date('g:i a', strtotime($slotArray[0])) .' - ' .date('g:i a', strtotime($slotArray[1]));
                    }
                    if(array_key_exists($carrierGroupId, $itemsGrouped)) {
                        foreach($itemsGrouped[$carrierGroupId] as $item) {
                            $item->setCarriergroupShipping($pickupText);
                        }
                    }

                }
            }

        }

    }

    public function savePickupToItems($items, $pickupInformation)
    {
        foreach($items as $item){
            $item->setCarriergroupShipping($pickupInformation);
            $item->save();
        }
    }

}
