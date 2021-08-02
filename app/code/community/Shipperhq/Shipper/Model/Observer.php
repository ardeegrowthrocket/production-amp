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

class Shipperhq_Shipper_Model_Observer extends Mage_Core_Model_Abstract
{
    /*
     * Refresh carriers in configuration pane
     *
     */
    public function updateTitles()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            $refreshResult = Mage::getModel('shipperhq_shipper/carrier_shipper')->refreshCarriers();
            if (array_key_exists('error', $refreshResult)) {
                $session = Mage::getSingleton('Mage_Adminhtml_Model_Session');
                $message = $refreshResult['error'];
                $session->addError($message);
            } else {
                $session = Mage::getSingleton('Mage_Adminhtml_Model_Session');
                $message = Mage::helper('shipperhq_shipper')->__('%s shipping methods have been updated from ShipperHQ', count($refreshResult));
                $session->addSuccess($message);
            }
        }
    }

    public function checkCartDisplayRequired($observer)
    {
        if ($block = $observer->getBlock() instanceof Mage_Checkout_Block_Cart_Shipping &&
            Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            $version = Mage::helper('wsalogger')->getNewVersion();
            if ($version >= 14) {
                $observer->getBlock()->setTemplate('shipperhq/checkout/cart/rwd/shipping.phtml');
            }
            else {
                $observer->getBlock()->setTemplate('shipperhq/checkout/cart/shipping.phtml');
            }
        }
    }

    public function oscUpdateCart($observer)
    {
        $body = $observer->getControllerAction()->getResponse()->getBody();
        $decodedBody = json_decode($body);

        $shqMethodHtml = Mage::app()->getLayout()
            ->createBlock('shipperhq_pickup/checkout_onepage_shipping_method_available')
            ->setTemplate('shipperhq/checkout/onestepcheckout/shipping_method_osc_shq_radio.phtml')
            ->toHtml();

        $decodedBody->shipping_method = $shqMethodHtml;

        $encodedBody = json_encode($decodedBody);

        $observer->getControllerAction()->getResponse()->setBody($encodedBody);
    }

    /*
     * Set renderer for dimensional shipping product attributes
     *
     */
    public function catalogProductEditSetRenderer($observer)
    {
        $form = $observer->getForm();

        $elementIds = array('shipperhq_volume_weight', 'shipperhq_poss_boxes', 'shipperhq_master_boxes'
        , 'shipperhq_volume_weight');
        foreach($elementIds as $element_id)
        {
            $element = $form->getElement($element_id);
            if($element) {
                $element->setRenderer(
                    Mage::app()->getLayout()->createBlock('shipperhq_shipper/adminhtml_catalog_product_edit_tab_dimensional')
                );
            }
        }
    }

    /**
     * Add the packing boxes form when editing a product
     *
     * @param Varien_Event_Observer $observer
     */
    public function prepareProductEditFormDimensional($observer)
    {

        $profileElement = $observer->getEvent()->getProductElement();
        // make the element dependent on shipperhq_dim_group
        $dependencies = Mage::app()->getLayout()->createBlock('adminhtml/widget_form_element_dependence',
            'adminhtml_recurring_profile_edit_form_dependence')->addFieldMap('shipperhq_dim_group', 'product[shipperhq_dim_group]')
            ->addFieldMap($profileElement->getHtmlId(), $profileElement->getName())
            ->addFieldDependence($profileElement->getName(), 'product[shipperhq_dim_group]', '');

        $dependencyOutput = $dependencies->toHtml();
        $observer->getEvent()->getResult()->output .= $dependencyOutput;

    }

    /**
     * Set flag for checkout on quote address
     *
     * @param object $observer
     */
    public function onCheckoutSaveBilling($observer)
    {
        $quote = Mage::helper('shipperhq_shipper')->getQuote();
        $shipping = $quote->getShippingAddress();
        $shipping->setIsCheckout(1);
        $billing = $quote->getBillingAddress();
        $billing->setIsCheckout(1);
    }

    public function multiCheckoutShippingPredispatch($observer)
    {
        $quote = $observer->getQuote();
        $addresses = $quote->getAllAddresses();
        foreach($addresses as $address)
        {
            $address->setIsCheckout(1);
        }
    }

    /**
     * Remove flag for checkout on quote address
     *
     * @param object $observer
     */
    public function onCheckoutCartEstimatePost($observer)
    {
        $quote = Mage::helper('shipperhq_shipper')->getQuote();
        $quote->setIsMultiShipping(false);
        $shipping = $quote->getShippingAddress();
        $shipping->setIsCheckout(0);
        $shipping->save();
    }

    /*
     * Set isCheckout flag on shipping address
     * Collect shipping rates again when Checkout loads otherwise it caches from cart
     *
     *@param object $observer
     */
    public function onOneStepCheckoutIndex($observer)
    {
        $quote = Mage::helper('shipperhq_shipper')->getQuote();
        $quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage($quote);
        $shipping = $quote->getShippingAddress();
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')
            && Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Splitrates')
            && !$shipping->getIsCheckout()) {
            $shipping->setCollectShippingRates(true);
        } //SHQ16-1134
        $shipping->setIsCheckout(1);
        $billing = $quote->getBillingAddress();
        $billing->setIsCheckout(1);
        $quoteStorage->setCalendarDetails(null);
        $quoteStorage->setSelectedDeliveryArray(null);
        $quoteStorage->setPickupArray(null);


    }

    public function saveOrderAfter($observer)
    {
        $order = $observer->getOrder();
        $shippingMethod = $order->getShippingMethod();

        if (!preg_match('/^shq|^shipper_|^multicarrier/', $shippingMethod)) {//SHQ16-1932
            $this->removeShqDetailsFromOrder($order);
        }

        if(Mage::getStoreConfig('carriers/shipper/active'))
        {
            $this->processSharedCarrier($order);
        }

        try
        {
            //  $recordOrderPackages = Mage::helper('shipperhq_shipper')->recordOrderPackages();
            $recordOrderPackages = true;

            if ($recordOrderPackages)
            {
                $quote = $order->getQuote();

                $shippingAddress = $quote->getShippingAddress();
                $orderId = $order->getId();
                if(is_null($orderId)) {
                    return;
                }
                $transactionId = false;
                $carrierGroupDetail = Mage::helper('shipperhq_shipper')->decodeShippingDetails(
                    $shippingAddress->getCarriergroupShippingDetails());
                if(is_array($carrierGroupDetail)){
                    foreach($carrierGroupDetail as $carrierGroup) {
                        if(!isset($carrierGroup['carrierGroupId'])) {
                            continue;
                        }
                        $carrierGroupId = $carrierGroup['carrierGroupId'];
                        $carrier_code = $carrierGroup['carrier_code'];
                        $shippingMethodCode = $carrierGroup['code'];
                        $sessionPackages = Mage::helper('shipperhq_shipper')->loadSessionPackagesByCarrier($shippingAddress->getAddressId(),
                            $carrierGroupId, $carrier_code, $shippingMethodCode);

                        foreach ($sessionPackages as $box) {

                            try {
                                $quotePackage = Mage::getModel('shipperhq_shipper/quote_packages');
                                $quotePackage->setData($box);
                                $quotePackage->save();
                            } catch (Exception $e) {
                                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper', 'Caught exception while saving package details to quote',
                                    "Box details were not saved to the quote, the following exception was caught: {$e->getMessage()}");
                            }

                            $orderPackage = Mage::getModel('shipperhq_shipper/order_packages');
                            unset($box['address_id']);
                            $orderPackage->setData($box);
                            $orderPackage->setOrderId($orderId);
                            $orderPackage->save();
                        }

                        if (Mage::helper('shipperhq_shipper')->storeDimComments()) {
                            if ($recordOrderPackages && count($sessionPackages) > 0) {
                                $boxText = Mage::helper('shipperhq_shipper')->getPackageBreakdownText($sessionPackages, $carrierGroup['name']);
                                $order->addStatusToHistory($order->getStatus(), $boxText, false);
                            }
                        }

                        if(array_key_exists('transaction', $carrierGroup)) {
                            $transactionId = $carrierGroup['transaction'];
                        }
                    }
                    //SHQ16-2476
                    $carrierGroupText = strip_tags($order->getCarriergroupShippingHtml(), "<br><b><strong>");
                    $order->addStatusToHistory($order->getStatus(), $carrierGroupText, false);
                    if ($transactionId) {
                        //SHIPPERHQ-1635
                        $order->addStatusToHistory($order->getStatus(), 'ShipperHQ Transaction ID: ' . $transactionId, false);
                    }
                    $order->save();
                } else {
                    $shippingMethod = $order->getShippingMethod();
                    if($rate = $quote->getShippingAddress()->getShippingRateByCode($shippingMethod)) {
                        $packagesColl = Mage::helper('shipperhq_shipper')->loadSessionPackagesByCarrier($shippingAddress->getAddressId(),
                            null, $rate->getCarrier(), '');

                        foreach ($packagesColl as $box) {
                            $quotePackage = Mage::getModel('shipperhq_shipper/quote_packages');
                            $quotePackage->setData($box);
                            $quotePackage->save();

                            $package = Mage::getModel('shipperhq_shipper/order_packages');
                            $package->setOrderId($orderId);
                            $package->setLength($box->getLength())
                                ->setWidth($box->getWidth())
                                ->setHeight($box->getHeight())
                                ->setWeight($box->getWeight())
                                ->setPackageName($box->getPackageName())
                                ->setDeclaredValue($box->getDeclaredValue())
                                ->setSurchargePrice($box->getSurchargePrice())
                                ->setCarrierGroupId($box->getCarrierGroupId())
                                ->setItems($box->getItems());
                            $package->save();
                        }
                        if(Mage::helper('shipperhq_shipper')->storeDimComments() &&
                            $recordOrderPackages && count($packagesColl) > 0)
                        {
                            $boxText = Mage::helper('shipperhq_shipper')->getPackageBreakdownText($packagesColl);
                            $order->addStatusToHistory($order->getStatus(), $boxText, false);
                            $order->save();
                        }
                    }

                }
            }
        }
        catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Called when shipping method is not from SHQ
     * Will remove SHQ elements from order to stop them being displayed
     *
     * @param $order
     */
    protected function removeShqDetailsFromOrder($order)
    {
        $order->setDispatchDate(null);
        $order->setDeliveryDate(null);
        $order->setCarriergroupShippingDetails(null);
        $order->setCarriergroupShippingHtml(null);
        $order->save();
    }

    /*
     * Process admin shipping
     */
    public function adminSalesOrderCreateProcessDataBefore($observer)
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            $post = $observer->getRequestModel()->getPost();
            if(isset($post['order'])) {
                $data = $post['order'];

                $found = false;
                $customCarrierGroupData = array();
                $carriergroupId = isset($data['carriergroup_id']) ? $data['carriergroup_id'] : '';
                if (isset($data['shipping_amount'])) {
                    $customCarrierGroupData[$carriergroupId]  = array('customPrice' => $data['shipping_amount'], 'carriergroup' => $carriergroupId);
                    $found = true;
                }

                if (isset($data['shipping_description'])) {
                    if(array_key_exists($carriergroupId, $customCarrierGroupData)) {
                        $shipArray = $customCarrierGroupData[$carriergroupId];
                        $shipArray['customCarrier'] = $data['shipping_description'];
                        $customCarrierGroupData[$carriergroupId] = $shipArray;
                    }
                    else {
                        $customCarrierGroupData[$carriergroupId]  = array('customCarrier' => $data['shipping_description'],  'carriergroup' => $carriergroupId);
                    }
                    $found = true;
                }

                if ($found) {
                    $shippingAddress =  $observer->getSession()->getQuote()->getShippingAddress();
                    Mage::helper('shipperhq_shipper')->cleanDownRatesCollection($shippingAddress, 'shipperadmin', '');
                    Mage::register('shqadminship_data', new Varien_Object($customCarrierGroupData));
                    $storedLimitCarrier = $shippingAddress->getLimitCarrier();
                    $shippingAddress->setLimitCarrier('shipperadmin');
                    $rateFound = $shippingAddress->requestShippingRates();
                    $shippingAddress->setLimitCarrier($storedLimitCarrier);
                } else {
                    Mage::unregister('shqadminship_data');
                }
            }
        }
    }

    public function salesConvertQuoteItemToOrderItem($observer)
    {
        try {
            if (!Mage::getStoreConfig('carriers/shipper/active')) {
                return;
            }
            $quoteItem = $observer->getEvent()->getItem();
            $orderItem = $observer->getEvent()->getOrderItem();
            $carriergroupId = $quoteItem->getCarriergroupId();

            $orderItem->setCarriergroupId($carriergroupId);
            $orderItem->setCarriergroup($quoteItem->getCarriergroup());
        } catch (Exception $e) {
            Mage::logException($e);
        }

    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function setCurrentQuoteObjectInAdmin(Varien_Event_Observer $observer)
    {
        Mage::helper('shipperhq_shipper')->setQuote(
            Mage::getSingleton('adminhtml/sales_order_create')->getQuote()
        );
    }

    public function setCurrentQuoteObjectInAdminFromSaveData(Varien_Event_Observer $observer)
    {

        $quote = $observer->getOrderCreateModel()->getQuote();
        $shipping = $quote->getShippingAddress();
        $shipping->setIsCheckout(1);
        $billing = $quote->getBillingAddress();
        $billing->setIsCheckout(1);

        $request = $observer->getRequestModel();
        if ($request->getActionName() === 'save') {
            $orderData = $request->getPost('order');

            if (isset($orderData['shipping_address'])) {
                unset($orderData['shipping_address']);
            }

            if (isset($orderData['billing_address'])) {
                unset($orderData['billing_address']);
            }

            if (isset($orderData['shipping_method'])) {
                unset($orderData['shipping_method']);
            }

            $request->setPost('order', $orderData);
            $request->setPost('shipping_as_billing', 0);
        }

        Mage::helper('shipperhq_shipper')->setQuote($observer->getOrderCreateModel()->getQuote());
    }

    /**
     * Loads storage data for quote if it was not loaded
     *
     * @param Varien_Event_Observer $observer
     */
    public function onQuoteAfterLoad(Varien_Event_Observer $observer)
    {
        $quote = $observer->getQuote();
        Mage::helper('shipperhq_shipper')->getQuoteStorage($quote);
    }

    /**
     * Saves storage data if quote is saved
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function onQuoteAfterSave(Varien_Event_Observer $observer)
    {
        $quote = $observer->getQuote();
        $storage = Mage::helper('shipperhq_shipper')->storageManager()->findByQuote($quote);
        $this->_saveStorageInstance($storage);
        return $this;
    }

    /**
     * Saves modified data objects on post dispatch,
     * if modifications has been done after quote has been saved
     *
     *
     */
    public function onPostDispatch()
    {
        /** @var Shipperhq_Shipper_Model_Storage[] $storageList */
        $storageList = Mage::helper('shipperhq_shipper')->storageManager()->getStorageObjects();
        foreach ($storageList as $storage) {
            if ($storage->hasDataChanges() && $storage->getId()) {
                $this->_saveStorageInstance($storage);
            }
        }
    }

    protected function processSharedCarrier($order)
    {
        if(strstr($order->getCarrierType(), 'shqshared_')) {
            $original  = $order->getCarrierType();
            $carrierTypeArray = explode('_', $order->getCarrierType());

            if(is_array($carrierTypeArray)) {
                $order->setCarrierType($carrierTypeArray[1]);
                //SHQ16-1026
                $carrierGroupDetail = $order->getCarriergroupShippingDetails();
                $currentShipDescription = $order->getShippingDescription();
                $shipDescriptionArray = explode('-', $currentShipDescription);
                $cgArray = Mage::helper('shipperhq_shipper')->decodeShippingDetails($carrierGroupDetail);
                foreach($cgArray as $key => $cgDetail) {
                    if(isset($cgDetail['carrierType']) && $cgDetail['carrierType'] == $original) {
                        $cgDetail['carrierType'] = $carrierTypeArray[1];
                    }
                    if(is_array($shipDescriptionArray) && isset($cgDetail['carrierTitle'])) {
                        $shipDescriptionArray[0] = $cgDetail['carrierTitle'] .' ';
                        $newShipDescription = implode('-', $shipDescriptionArray);
                        if(!Mage::helper('shipperhq_shipper')->getAlwaysShowSingleCarrierTitle()) {
                            $order->setShippingDescription($newShipDescription);
                        }
                    }
                    $cgArray[$key] = $cgDetail;
                }
                $order->setCarriergroupShippingDetails(Mage::helper('shipperhq_shipper')->encodeShippingDetails($cgArray));
                $order->save();
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Rates displayed as single carrier',
                        'Resetting carrier type on order to be ' .$carrierTypeArray[1]);
                }
            }
        }
    }

    /**
     * Saves storage instance
     *
     * @param Shipperhq_Shipper_Model_Storage $storage
     * @return $this
     * @throws Exception
     */
    protected function _saveStorageInstance(Shipperhq_Shipper_Model_Storage $storage)
    {
        if ($storage->isLoaded() && $storage->isEmpty()) {
            // When object is empty, we delete database record
            $storage->isDeleted(true);
        } elseif ($storage->isEmpty()) {
            // If object is new and has no data, than do not save
            return $this;
        } elseif ($storage->isDeleted()) {
            // If it was deleted, remove a flag
            $storage->isDeleted(false);
        }

        if (!$storage->isValid(true)) {
            return $this;
        }

        try {
            $storage->save();
        } catch (Exception $e) {
            Mage::logException($e);
            // Do not break quote save process
        }

        return $this;
    }
}
