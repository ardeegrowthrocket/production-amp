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

class Shipperhq_Splitrates_Model_Checkout_Helper
{
    public function saveCarriergroupShippingMethod(Mage_Sales_Model_Quote $quote, $shippingMethod, $setCollectedFlag = false, $save = true)
    {
        $shippingAddress=$quote->getShippingAddress();
        if (empty($shippingMethod) ) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('shipperhq_splitrates')->__('Please select shipping method(s).')
            );
            return $res;
        }

        $shippingRateGroups = $shippingAddress->getGroupedAllShippingRates();

        $shippingRates = Mage::helper('shipperhq_splitrates')->manuallyMergeShippingRates($shippingRateGroups, $shippingMethod, false);
        
        if(array_key_exists('error' , $shippingRates)){
            return $shippingRates;
        }

        Mage::helper('shipperhq_splitrates')->createShippingRate($shippingRates, $shippingAddress);
        $quote->save();
        $shippingDetails = false;
        $mergedShippingMethodName = null;
        $pickupChosen = false;
        $optionCodes = array();
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight')) {
            $optionCodes = Mage::helper('shipperhq_freight')->getAllPossibleOptions();
        }

        foreach($shippingRates as $rate) {
            $thisShipDetails =  Mage::helper('shipperhq_shipper')->decodeShippingDetails($rate['shipping_details']);
            $carriergroup = $rate['carriergroup'];
            $carrier_code = $rate['carrier_code'];
            $carrier_type = $rate['carrier_type'];
            if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup') && Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($carrier_type)) {
                if(array_key_exists('location_id_'.$carrier_code.'_'.$carriergroup, $shippingMethod)) {
                    $thisShipDetails['location_id'] = $shippingMethod['location_id_'.$carrier_code.'_'.$carriergroup];
                    $thisShipDetails['pickup_date'] = $shippingMethod['pickup_date_'.$carrier_code.'_'.$carriergroup];
                    if(array_key_exists('pickup_slot_'.$carrier_code.'_'.$carriergroup, $shippingMethod) &&
                        $shippingMethod['pickup_slot_'.$carrier_code.'_'.$carriergroup] != '') {
                        $thisShipDetails['pickup_slot'] = $shippingMethod['pickup_slot_'.$carrier_code.'_'.$carriergroup];
                    }
                    $pickupChosen = true;
                }
            }
            if(array_key_exists('del_date_'.$carrier_code.'_'.$carriergroup, $shippingMethod)) {
                $thisShipDetails['delivery_date'] = $shippingMethod['del_date_'.$carrier_code.'_'.$carriergroup];
                if(array_key_exists('del_slot_'.$carrier_code.'_'.$carriergroup, $shippingMethod) &&
                    $shippingMethod['del_slot_'.$carrier_code.'_'.$carriergroup] != '') {
                    $thisShipDetails['del_slot'] = $shippingMethod['del_slot_'.$carrier_code.'_'.$carriergroup];
                }
            }
            //Freight options
            foreach($optionCodes as $code) {
                if(array_key_exists($code .'_'.$carrier_code.'_'.$carriergroup, $shippingMethod)) {
                    $thisShipDetails[$code] = $shippingMethod[$code.'_'.$carrier_code.'_'.$carriergroup];
                }
            }
            $shippingDetails[] = $thisShipDetails;
            $mergedShippingMethodName =  'shipper_'.$rate['mergedTitle'];

        }
        if($shippingDetails) {
            Mage::helper('shipperhq_shipper')->setShippingOnItems($shippingDetails, $shippingAddress);
            $encodedShipDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($shippingDetails);
            $quote->getShippingAddress()
                ->setCarriergroupShippingDetails($encodedShipDetails)
                ->setCarriergroupShippingHtml(Mage::helper('shipperhq_shipper')->getCarriergroupShippingHtml($encodedShipDetails))
                ->save();
        }

        if ($pickupChosen) {
            Mage::helper('shipperhq_pickup')->setPickupChosenOnCarriergroupItems($shippingDetails, $shippingAddress);
        }
        
        $quote->setTotalsCollectedFlag($setCollectedFlag);
        $quote->collectTotals()->save();

        return $mergedShippingMethodName;
    }

}