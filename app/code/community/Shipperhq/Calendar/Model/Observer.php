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

class Shipperhq_Calendar_Model_Observer extends Mage_Core_Model_Abstract
{


    /**
     * Save the dates selected on shipping address in admin
     *
     */
    public function saveDateSelectedInAdmin($observer)
    {
        $requestData = $observer->getRequest();
        $orderData = array();
        if (isset($requestData['order'])) {
            $orderData = $requestData['order'];
            if(isset($requestData['shipping_method_flag'])) {
                $orderData = $requestData;
            }
        }
        if ($orderData
            && !empty($orderData['shipping_method_flag'])
            && !empty($orderData['shipping_method'])) {
            $shippingmethod = $orderData['shipping_method'];
            $quote = $observer->getOrderCreateModel();
            $shippingAddress = $quote->getShippingAddress();
            $result = $this->saveDeliveryInfoToShippingAddress($shippingAddress, $shippingmethod, $orderData);
        }
    }

    /*
     * Save the dates selected on shipping address
     *
     */

    public function saveDateSelected($observer)
    {
        $shippingmethod = $observer->getEvent()->getRequest()->getParam('shipping_method');
        if($shippingmethod == '') {
            return;
        }
        $shippingAddress = $observer->getEvent()->getQuote()->getShippingAddress();
        $data = $observer->getEvent()->getRequest()->getParams();
        $result = $this->saveDeliveryInfoToShippingAddress($shippingAddress, $shippingmethod, $data);
    }

    public function saveDateSelectedMulti($observer)
    {
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Calendar')) {
            return;
        }
        $shippingMethods = $observer->getEvent()->getRequest()->getPost('shipping_method');
        $quote = $observer->getEvent()->getQuote();
        if(!is_array($shippingMethods)) {
            return;
        }
        foreach ($shippingMethods as $addressId => $shipping_method) {

            $shippingAddress = false;
            foreach($quote->getAllShippingAddresses() as $address){
                if($address->getId() == $addressId) {
                    $shippingAddress = $address;
                    break;
                }
            }
            if($shippingAddress) {
                $data = $observer->getEvent()->getRequest()->getParams();
                $suffix = '_ma'.$addressId;
                $shippingDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails(
                    $shippingAddress->getCarriergroupShippingDetails()
                );
                if(count($shippingDetails) == 1) { //SHQ16-2023 this should be ignored for split shipping
                    $carriergroupId = $shippingDetails[0]['carrierGroupId'];
                    $suffix .= 'ZZ'.$carriergroupId;
                }
                $result = $this->saveDeliveryInfoToShippingAddress($shippingAddress, $shipping_method, $data, $suffix);
            }
        }
    }

    public function recordDeliveryInfo($observer) {

        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Calendar')) {
            return;
        }
        $order =  $observer->getOrder();
        $deliveryDate = $order->getDeliveryDate();
        if($deliveryDate && $deliveryDate != '') {

            $addresses = $order->getQuote()->getAllShippingAddresses();
            $shippingAddress = false;
            foreach($addresses as $address) {
                if($address->getDeliveryDate() == $deliveryDate) {
                    $shippingAddress = $address;
                    break;
                }
            }
            //skip if it's pickup order
            $isPickup = Mage::helper('shipperhq_shipper')->isPickupRate($shippingAddress, $order->getShippingMethod());

            if ($shippingAddress && !$isPickup) {
                $shippingDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails($order->getCarriergroupShippingDetails());
                if(is_array($shippingDetails)) {
                    foreach($shippingDetails as $detail) {
                        if(is_array($detail) && array_key_exists('dispatch_date', $detail)) {
                            $shippingAddress->setDispatchDate($detail['dispatch_date']);
                            $order->setDispatchDate($detail['dispatch_date']);
                        }
                        break;
                    }
                }
                $shippingAddress->save();
                $order->save();
            }

        }
    }

    public function setSelectedDeliveryDates($observer)
    {

        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Calendar')) {
            return;
        }
        $request =  $observer->getRequest();
        $selectedDeliveryDate = Mage::getSingleton('core/session')->getSelectedDeliveryArray();
        if(Mage::getSingleton('core/session')->getProductPageEstimate() &&
        $selectedDeliveryDate && $request->getDeliveryDateSelected() == '') {
            if(array_key_exists('date_selected', $selectedDeliveryDate)) {
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

    }

    protected function saveDeliveryInfoToShippingAddress($shippingAddress, $shippingmethod, $orderData, $suffix = '')
    {
        $groups = $shippingAddress->getGroupedAllShippingRates();
        $shippingRateSelected = false;
        foreach($groups as $code => $rates) {
            foreach($rates as $rate) {
                if($rate->getCode() == $shippingmethod) {
                    $shippingRateSelected = $rate;
                    break;
                }
            }
            if($shippingRateSelected != false) break;
        }

        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Calendar') || !$shippingRateSelected) {
            return false;
        }

        $code = $shippingRateSelected->getCarrier();
        $delDateFieldName = 'del_date_' .$code .$suffix;
        $dateSelected = (isset($orderData[$delDateFieldName]) ? $orderData[$delDateFieldName] : null);
        if($dateSelected == null) {
            return false;
        }

        $cgDetail = $rate->getCarriergroupShippingDetails();

        $decodedCgDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails($cgDetail);
        $dateFormat = isset($decodedCgDetails['dateFormat']) ?
            $decodedCgDetails['dateFormat'] : Mage::helper('shipperhq_shipper')->getZendDateFormat(); //SHQ18-1090

        $deliveryDate = Mage::app()->getLocale()->date($dateSelected, null, null, false)->toString($dateFormat);
        if($deliveryDate != $shippingRateSelected->getDeliveryDate()) {
            Mage::helper('wsalogger/log')->postCritical('Shipperhq Calendar',
                'Delivery date in shipping rates does not match selected date ',
                array('Rate ' =>$shippingRateSelected->getCode(),
                    'rate delivery date'=> $shippingRateSelected->getDeliveryDate(),
                    'date selected' =>$deliveryDate));

            $shippingAddress->setDispatchDate('');

        }
        else {
            $shippingAddress->setDispatchDate($shippingRateSelected->getDispatchDate());
        }

        $shippingAddress->setDeliveryDate($deliveryDate);
        $timeSlotFieldName = 'del_slot_' .$code .$suffix;
        $timeSlot = (isset($orderData[$timeSlotFieldName]) ? $orderData[$timeSlotFieldName] : null);

        if($timeSlot != null) {
            $timeSlot = str_replace('_', ' - ', $timeSlot);
            $shippingAddress->setTimeSlot($timeSlot);
        }
        $cgDetail = $this->addDeliveryToCgDetail($shippingAddress->getCarriergroupShippingDetails(), $shippingAddress);
        $shippingAddress->setCarriergroupShippingDetails($cgDetail)
            ->setCarriergroupShippingHtml(Mage::helper('shipperhq_shipper')->getCarriergroupShippingHtml(
                $cgDetail));
        $shippingAddress->save();
    }

    protected function addDeliveryToCgDetail($encodedDetails, $shippingAddress)
    {
        $carrierGroupShippingDetail = Mage::helper('shipperhq_shipper')->decodeShippingDetails($encodedDetails);
        foreach($carrierGroupShippingDetail as $key => $shipDetail) {
            if($shippingAddress->getDeliveryDate() != '') {
                $shipDetail['delivery_date'] = $shippingAddress->getDeliveryDate();
                $shipDetail['dispatch_date'] = $shippingAddress->getDispatchDate();
                if($shippingAddress->getTimeSlot()!= '') {
                    $shipDetail['del_slot'] = $shippingAddress->getTimeSlot();
                }
                $carrierGroupShippingDetail[$key] = $shipDetail;

            }
        }
        $encodedDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($carrierGroupShippingDetail);
        return $encodedDetails;
    }

}