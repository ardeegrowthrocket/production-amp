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

class Shipperhq_Pickup_Model_Observer extends Mage_Core_Model_Abstract
{
    /*
     * Request shipping rates so we can determine if pickup is available for items in cart
     */
    public function hookCheckoutMultishippingIndexPostdispatch($observer)
    {
        $quote = Mage::helper('shipperhq_shipper')->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->save();
        $addresses = $quote->getAllAddresses();
        foreach($addresses as $address)
        {
            $address->setCollectShippingRates(true);
        }
        $shippingAddress->collectShippingRates();
        $shippingAddress->save();
    }

    /**
     * Save the pickup location as shipping address on this order in admin$
     *
     */
    public function savePickupLocationInAdmin($observer)
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
            $quote = $observer->getOrderCreateModel()->getQuote();
            $shipping_method = $orderData['shipping_method'];
            $carrierCode = Mage::helper('shipperhq_shipper')->isPickupRate($quote->getShippingAddress(), $shipping_method);

            if($shipping_method == '' || !$carrierCode) {
                return;
            }

            $pickupLocationId = isset($orderData['location_id_' . $carrierCode]) ? $orderData['location_id_' . $carrierCode] : null;
            $pickupDate = isset($orderData['pickup_date_' . $carrierCode]) ? $orderData['pickup_date_' . $carrierCode] : null;
            $pickupSlot = isset($orderData['pickup_slot_' . $carrierCode]) ? $orderData['pickup_slot_' . $carrierCode] : null;

            if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')) {
                return;
            }

            $shippingAddress = $quote->getShippingAddress();
            $this->_savePickupInfoToShippingAddress($shippingAddress, 0, $pickupLocationId, $carrierCode, $pickupDate, $pickupSlot);
        }
    }

    /*
     * Save the pickup location as shipping address on this order
     *
     */

    public function savePickupLocation($observer)
    {
        if (Mage::helper('shipperhq_shipper')->isModuleEnabled('Idev_OneStepCheckout', 'onestepcheckout/general/rewrite_checkout_links')) {
            if($observerObj = $observer->getControllerAction()) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
            }
            else {
                $observerObj = $observer->getEvent();
                $quote = $observer->getEvent()->getQuote();
            }
        } else {
            $observerObj = $observer->getEvent();
            $quote = $observer->getEvent()->getQuote();
        }

        if(!is_object($observerObj)) {
            return null;
        }

        $shipping_method = $observerObj->getRequest()->getParam('shipping_method');
        $carrierCode = Mage::helper('shipperhq_shipper')->isPickupRate($quote->getShippingAddress(), $shipping_method);
        $shippingAddress = $quote->getShippingAddress();

        if($shipping_method == '' || !$carrierCode) {
            /**
             * SHQ16-1467 - Revert address to customer entered one if pickup is deselected.
             */
            if($shippingAddress->getOrigShippingAddress() != null && $shippingAddress->getOrigShippingAddress() != '') {
                $this->restoreOriginalShipAddress($shippingAddress);
            }
            return;
        }

        $pickupLocationId = $observerObj->getRequest()->getParam('location_id_'.$carrierCode);
        $pickupDate = $observerObj->getRequest()->getParam('pickup_date_'.$carrierCode);
        $pickupSlot = $observerObj->getRequest()->getParam('pickup_slot_'.$carrierCode);
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup') || !$pickupLocationId) {
            return;
        }


        $this->_savePickupInfoToShippingAddress($shippingAddress, 0, $pickupLocationId, $carrierCode, $pickupDate, $pickupSlot);
    }

    /*
     * Save the pickup location as shipping address on this order
     *
     */

    //todo: add in CG details

    public function savePickupLocationMulti($observer)
    {
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')) {
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
                }
            }
            if(!$shippingAddress) {
                break;
            }
            $carrierCode = Mage::helper('shipperhq_shipper')->isPickupRate($shippingAddress, $shipping_method);
            if($shipping_method == '' || !$carrierCode) {
                if($shippingAddress->getOrigShippingAddress() != null && $shippingAddress->getOrigShippingAddress() != '') {
                    $this->restoreOriginalShipAddress($shippingAddress);
                }
                continue;
            }
            $data = $observer->getEvent()->getRequest()->getParams();
            $suffix = '_ma'.$addressId;
            $shippingDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails(
                $shippingAddress->getCarriergroupShippingDetails()
            );
            if(count($shippingDetails) == 1) { //SHQ16-2023 this should be ignored for split shipping
                $carriergroupId = $shippingDetails[0]['carrierGroupId'];
                $suffix .= 'ZZ'.$carriergroupId;
            }

            $pickupLocationId = $observer->getEvent()->getRequest()->getParam('location_id_' .$carrierCode.$suffix);
            $pickupDate = $observer->getEvent()->getRequest()->getParam('pickup_date_' .$carrierCode .$suffix);
            $pickupSlot = $observer->getEvent()->getRequest()->getParam('pickup_slot_' .$carrierCode .$suffix);
            if(!$pickupLocationId) {
                continue;
            }

            $this->_savePickupInfoToShippingAddress($shippingAddress, 0, $pickupLocationId, $carrierCode, $pickupDate, $pickupSlot);
        }

    }

    public function hookToControllerActionPreDispatch($observer) {

        $actionName = $observer->getEvent()->getControllerAction()->getFullActionName();
        switch ($actionName) {
            case 'checkout_onepage_saveShipping':
            case 'checkout_onepage_saveBilling':
            case 'opc_json_saveBilling':
            case 'opc_json_saveShipping':
                Mage::dispatchEvent("shipperhq_save_shipping_before",
                    array(
                        'request' => $observer->getControllerAction()->getRequest(),
                        'quote' => Mage::helper('shipperhq_shipper')->getQuote()
                    ));
                break;
        }

    }

    public function saveShippingBefore($observer) {

        Mage::getSingleton('checkout/session')->setFreemethodWeight(null); // @todo what is this?? (Ivan)
        $quote = $observer->getQuote();
        Mage::helper('shipperhq_shipper')->getQuoteStorage($quote)
            ->setPickupArray(array());
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setPickupLocation("");
        $shippingAddress->save();
    }

    public function recordPickupSlot($observer) {

        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')) {
            return;
        }
        $order =  $observer->getOrder();
        $location = $order->getPickupLocation();
        $pickupDate = $order->getDispatchDate();
        $timeSlot = $order->getTimeSlot();
        if($location && $location != '') {
            if($pickupDate != '') {
                $addresses = $order->getQuote()->getAllShippingAddresses();
                $shippingAddress = false;
                foreach($addresses as $address) {
                    if($address->getPickupDate() == $pickupDate &&
                        $address->getTimeSlot() == $timeSlot &&
                        $address->getPickupLocation() == $location) {
                        $shippingAddress = $address;
                        break;
                    }
                }
                if ($shippingAddress) {
                    $currentShipDescription = $shippingAddress->getShippingDescription();
                    $shipDescriptionArray = explode('-', $currentShipDescription);
                    $newShipDescription = trim($shipDescriptionArray[1]);
                    if($pickupDate) {
                        $location .= ' ' .$pickupDate ;
                    }

                    if($timeSlot) {
                        $slotArray = explode(' - ', $timeSlot);
                        $location .= ' ' .date('g:i a', strtotime($slotArray[0])) .' - ' .date('g:i a', strtotime($slotArray[1]));
                    }
                    $newShipDescription.= ': ' .$location;
                    $shippingAddress->setShippingDescription($newShipDescription);
                    $shippingAddress->save();
                    $order->setShippingDescription($newShipDescription);
                    $order->save();
                }
            }
        }
    }

    protected function _savePickupInfoToShippingAddress($shippingAddress, $carrierGroupId, $pickupLocationId, $carrierCode, $pickupDate, $pickupSlot = null)
    {
        if ($pickupDate === '') {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Pickup', 'Saving pickup date',
                    'Pickup selected but date field is blank for shipping address ID ' .$shippingAddress->getId() .', order in name of ' .$shippingAddress->getName() );
            }
        }
        $shippingAddress->setDispatchDate($pickupDate);
        $shippingAddress->setDeliveryDate($pickupDate);
        $shippingAddress->setPickupDate($pickupDate);

        $pickupLocation = Mage::helper('shipperhq_pickup')->getLocationDetails($carrierGroupId, $carrierCode, $pickupLocationId);
        if($pickupLocation) {
            $this->saveOriginalShipAddress($shippingAddress);

            $regionModel = Mage::getModel('directory/region')->loadByName($pickupLocation['state'], $pickupLocation['country']);
            $regionId = $regionModel->getId();
            $shippingAddress->setPickupLocation($pickupLocation['pickupName']);
            $shippingAddress->setPickupLatitude($pickupLocation['latitude']);
            $shippingAddress->setPickupLongitude($pickupLocation['longitude']);
            $shippingAddress->setPickupEmail($pickupLocation['email']);
            $shippingAddress->setPickupContact($pickupLocation['contactName']);
            $shippingAddress->setPickupEmailOption($pickupLocation['emailOption']);
            $shippingAddress->setPickupLocationId($pickupLocation['publicId']);
            $pickupText = $shippingAddress->getShippingDescription() .' ' .$pickupLocation['pickupName']. ' ' .$pickupDate ;
            if($pickupSlot != '') {
                $pickupSlot = str_replace('_', ' - ', $pickupSlot);
                $slotArray = explode(' - ', $pickupSlot);
                $pickupText .= ' ' .date('g:i a', strtotime($slotArray[0])) .' - ' .date('g:i a', strtotime($slotArray[1]));
            }
            $shippingAddress->setTimeSlot($pickupSlot);
            Mage::helper('shipperhq_pickup')->savePickupToItems($shippingAddress->getAllItems(), $pickupText);

            $street = '';
            if(array_key_exists('street1', $pickupLocation) && $pickupLocation['street1'] != "") {
                $street = $pickupLocation['street1'];
            }
            if(array_key_exists('street2', $pickupLocation) && $pickupLocation['street2'] != "") {
                $street .= $pickupLocation['street2'];
            }
            if($street != '') {
                $shippingAddress->setStreet($street);
            }
            $shippingAddress->setCompany($pickupLocation['pickupName']);
            $shippingAddress->setCity($pickupLocation['city']);
            $shippingAddress->setPostcode($pickupLocation['zipcode']);
            $shippingAddress->setRegion($pickupLocation['state']);
            if (isset($regionId)) {
                $shippingAddress->setRegionId($regionId);
            }
            $shippingAddress->setCountryId($pickupLocation['country']);
            $cgDetail = $shippingAddress->getCarriergroupShippingDetails();
            $cgDetail = $this->addPickupToCgDetail($cgDetail, $shippingAddress);
            $shippingAddress->setCarriergroupShippingDetails($cgDetail)
            ->setCarriergroupShippingHtml(Mage::helper('shipperhq_shipper')->getCarriergroupShippingHtml(
                    $cgDetail));
        }
        $shippingAddress->save();
    }

    protected function addPickupToCgDetail($encodedDetails, $shippingAddress)
    {
        $carrierGroupShippingDetail = Mage::helper('shipperhq_shipper')->decodeShippingDetails($encodedDetails);

        foreach($carrierGroupShippingDetail as $key => $shipDetail) {
            if($shippingAddress->getPickupLocation() != '') {
                $shipDetail['location_name'] = $shippingAddress->getPickupLocation();
                $shipDetail['location_id'] = $shippingAddress->getPickupLocationId();
                if($shippingAddress->getPickupDate() != '') {
                    $shipDetail['pickup_date'] = $shippingAddress->getPickupDate();
                }

                if($shippingAddress->getTimeSlot()!= '') {
                    $shipDetail['pickup_slot'] = $shippingAddress->getTimeSlot();
                }

                if($shippingAddress->getPickupContact() != '') {
                    $shipDetail['pickup_contact'] = $shippingAddress->getPickupContact();

                }

                if($shippingAddress->getPickupEmail() != '') {
                    $shipDetail['pickup_email'] = $shippingAddress->getPickupEmail();

                }

                if($shippingAddress->getPickupEmailOption() != '') {
                    $shipDetail['pickup_email_option'] = $shippingAddress->getPickupEmailOption();

                }
                $carrierGroupShippingDetail[$key] = $shipDetail;

            }
        }
       $encodedDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($carrierGroupShippingDetail);
       return $encodedDetails;
    }

    /**
     * Save customer entered address to the shipping address model in case we need to revert to that address
     * SHQ16-1467
     *
     * @param $shippingAddress
     */
    protected function saveOriginalShipAddress($shippingAddress)
    {
        $origShipAddress = $shippingAddress->getOrigShippingAddress();

        if($origShipAddress == "" || $origShipAddress == null) {
            $origAddressArr['street'] =  $shippingAddress->getStreet();
            $origAddressArr['company'] = $shippingAddress->getCompany();
            $origAddressArr['city'] = $shippingAddress->getCity();
            $origAddressArr['zipcode'] = $shippingAddress->getPostcode();
            $origAddressArr['state'] = $shippingAddress->getRegion();
            $origAddressArr['country'] = $shippingAddress->getCountryId();
            $origAddressArr['region_id'] = $shippingAddress->getRegionId();

            $encodedDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($origAddressArr);
            $shippingAddress->setOrigShippingAddress($encodedDetails);
        }
    }

    /**
     * Overwrite the pickup address with the customer entered address.
     * SHQ16-1467
     *
     * @param $shippingAddress
     */
    protected function restoreOriginalShipAddress($shippingAddress)
    {
        $encodedOrigShipAddress = $shippingAddress->getOrigShippingAddress();
        $origShipAddress = Mage::helper('shipperhq_shipper')->decodeShippingDetails($encodedOrigShipAddress);

        $shippingAddress->setStreet($origShipAddress['street']);
        $shippingAddress->setCompany($origShipAddress['company']);
        $shippingAddress->setCity($origShipAddress['city']);
        $shippingAddress->setPostcode($origShipAddress['zipcode']);
        $shippingAddress->setRegion($origShipAddress['state']);
        $shippingAddress->setCountryId($origShipAddress['country']);
        $shippingAddress->setRegionId($origShipAddress['region_id']);
    }

    protected function _getCustomer()
    {
        return $this->_getCustomerSession()->getCustomer();
    }

    /**
     * Retrieve customer session vodel
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        $customer = $this->getData('customer_session');
        if (is_null($customer)) {
            $customer = Mage::getSingleton('customer/session');
            $this->setData('customer_session', $customer);
        }
        return $customer;
    }

}