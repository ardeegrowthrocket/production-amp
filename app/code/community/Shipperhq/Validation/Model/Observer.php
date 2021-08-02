<?php
/**
 * Magento
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
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
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


class Shipperhq_Validation_Model_Observer extends Mage_Core_Model_Abstract
{

    protected $debug;

    public function hookToControllerActionPreDispatch($observer)
    {
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation', 'carriers/shipper/AV_ENABLED')) {
            return;
        }
        $actionName = $observer->getEvent()->getControllerAction()->getFullActionName();
        switch ($actionName) {
            case 'customer_address_formPost':
                $this->customerAddressFormPredispatch($observer);
                break;
            case 'checkout_onepage_saveBilling':
                $this->saveBillingQuoteBefore($observer);
                break;
            case 'checkout_onepage_saveShipping':
                $this->saveShippingQuoteBefore($observer);
                break;
            case 'onestepcheckout_ajax_save_billing':
                $this->onestepcheckoutSaveBillingBefore($observer);
                break;
            case 'firecheckout_index_saveOrder':
                $this->_validateFirecheckoutAddress($observer);
                break;
        }
    }

    public function hookToControllerActionPostDispatch($observer)
    {
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation', 'carriers/shipper/AV_ENABLED')) {
            return;
        }
        $actionName = $observer->getEvent()->getControllerAction()->getFullActionName();
        switch ($actionName) {
            /*    case 'checkout_cart_estimatePost':
                case 'loworderfee_cart_estimatePost': //Added for compatibility with Mango Low Order Notification
                    if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Wsafreightcommon')) {
                        $this->_estimatePostQuote($observer);
                    }
                    break;*/
            case 'checkout_onepage_saveBilling':
                $this->saveBillingQuoteAfter($observer);
                break;
            case 'checkout_onepage_saveShipping':
                $this->saveShippingQuoteAfter($observer);
                break;
        }
    }

    protected function _validateFirecheckoutAddress($observer)
    {
        $paymentData = $observer->getControllerAction()->getRequest()->getPost('payment', array());
        Mage::getSingleton('firecheckout/type_standard')
            ->applyPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);

        // validate billing address
        $this->saveBillingQuoteBefore($observer);
        $this->saveBillingQuoteAfter($observer);

        $controller = $observer->getControllerAction();
        if ($controller->getResponse()->getBody()) { // validatiopn window is in response
            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
        } else {
            // validate shipping address if needed
            $shipping = $controller->getRequest()->getPost('shipping');
            if (!$shipping || (isset($shipping['same_as_billing']) && $shipping['same_as_billing'])) {
                return;
            }

            Mage::unregister('Shipperhq_Destination_Type');
            $this->saveShippingQuoteBefore($observer);
            $this->saveShippingQuoteAfter($observer);
            if ($controller->getResponse()->getBody()) { // validatiopn window is in response
                $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            }
        }
    }

    /**
     * Event sales_order_place_after
     * Validate shipping address, if required
     * @param $observer
     */
    public function verifyShippingAddress($observer)
    {
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation', 'carriers/shipper/AV_ENABLED')) {
            return;
        }
        $order =  $observer->getOrder();
        if($order->getQuote()->isVirtual()) {
            return;
        }
        $this->debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Validation');

        $addresses = $order->getQuote()->getAllShippingAddresses();
        $validator = Mage::getModel('shipperhq_validation/validator');
        if($validator) {
            foreach($addresses as $address) {
                $validValue = $address->getAddressValid();
                $customerAddress = false;
                if($address->getCustomerAddressId()) {
                    $customerAddress = Mage::getModel('customer/address')->load($address->getCustomerAddressId());
                    $validValue = $customerAddress->getAddressValid();
                }
                if(Mage::helper('shipperhq_validation')->shouldRevalidate($validValue)) {

                    $results = $validator->validateAddress($address);
                    if($results && $outcome = $results->getOutcome()) {

                        $address->setAddressValid($outcome);
                        $address->save();
                        $order->getShippingAddress()->setAddressValid($outcome);
                        if($address->getSameAsBilling()) {
                            $billing = $order->getQuote()->getBillingAddress();
                            if($billing) {
                                $billing->setAddressValid($outcome)
                                    ->save();
                            }
                        }

                        if($customerAddress) {
                            $customerAddress->setAddressValid($outcome);
                            $customerAddress->save();
                        }
                    }
                }

            }
        }

    }

    /**
     * Event: adminhtml_block_html_before
     * Change template of Order View to display address validation result
     * @param $observer
     */
    public function onAdminhtmlBlockHtmlBefore($event)
    {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View_Info && $block->getNameInLayout() != 'postorder_info') {
            if (Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation',
                'carriers/shipper/AV_ENABLED'))
                {
                $block->setTemplate('shipperhq/frontend/sales/order/view/info.phtml');
            }
        }
    }

    /**
     *
     * Set the address type if known
     * @param $observer
     */
    private function saveBillingQuoteBefore($observer)
    {

        if(Mage::getSingleton('checkout/session')->getQuote()->isVirtual() ||
            !Mage::helper('shipperhq_validation')->validateAtCheckout()) {
            return;
        }

        $request = $observer->getControllerAction()->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost('billing', array());
            $alreadyValidated = array_key_exists('address_valid', $data) ? (string)$data['address_valid']: '';
            $useForShipping = $data['use_for_shipping'];
            $destinationType = array_key_exists('destination_type', $data) ? (string)$data['destination_type']: null;

            if($useForShipping) {
                Mage::unregister('Shipperhq_Destination_Type');
                Mage::register('Shipperhq_Destination_Type', $destinationType);
            }
            $customerAddressId = $request->getPost('billing_address_id', false);
            $selectedValidatedAddress = $data['updated_address_flag'];
            if (!empty($customerAddressId)) {
                $customerAddress = Mage::helper('shipperhq_validation')->getCustomerAddress($customerAddressId);
                if ($customerAddress->getId()) {
                    $customerAddress->setAddressValid($alreadyValidated);
                    if(!is_null($destinationType) && $destinationType != '') {
                        $customerAddress->setDestinationType($destinationType);
                    }
                    $customerAddress->save();

                }
            }
            elseif($selectedValidatedAddress != 0) {
                $customerAddress = Mage::helper('shipperhq_validation')->getCustomerAddress($selectedValidatedAddress);
                if ($customerAddress->getId()) {

                    $this->updateCustomerAddress($customerAddress, $data, $alreadyValidated);
                }
            }
            if (!Mage::helper('shipperhq_validation')->shouldRevalidate($alreadyValidated)|| !$useForShipping) {
                return;
            }
            Mage::unregister('Shipperhq_Validation_Results');
            $validator = Mage::getModel('shipperhq_validation/validator');
            if($validator) {
                $results = $validator->validateAddress($data);
                if($results && $outcome = $results->getOutcome()) {
                    $data['address_valid'] = $outcome;
                    $request->setPost('billing', $data);
                    Mage::register('Shipperhq_Validation_Results', $results, true);
                    $candidateCount = count($results->getCandidates());
                    if($candidateCount == 1 && $outcome == Shipperhq_Shipper_Model_Source_Validation_Result::VALID) {
                        $candidates = $results->getCandidates();
                        $destinationType = $candidates[0]['destination_type'];
                        Mage::unregister('Shipperhq_Destination_Type');
                        Mage::register('Shipperhq_Destination_Type', $destinationType);
                        if($customerAddressId && $customerAddress) {
                            if($destinationType != '') {
                                $customerAddress->setDestinationType($destinationType);
                            }
                            $customerAddress->setAddressValid($outcome);
                            $customerAddress->save();
                        }
                    }
                }

            }

        }

    }

    private function saveBillingQuoteAfter($observer)
    {
        if(Mage::getSingleton('checkout/session')->getQuote()->isVirtual() ||
            !Mage::helper('shipperhq_validation')->validateAtCheckout()) {
            return;
        }

        $request = $observer->getControllerAction()->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost('billing', array());

            $useForShipping = $data['use_for_shipping'];
            if(!$useForShipping) {
                return;
            }
            $results = Mage::registry('Shipperhq_Validation_Results');
            if($results && $outcome = $results->getOutcome()) {
                $validator = Mage::getModel('shipperhq_validation/validator');
                $data['address_valid'] = $outcome;
                $request->setPost('billing', $data);
                $controller = $observer->getEvent()->getData('controller_action');
                $controller->loadLayout('checkout_onepage_index');
                $display =  $validator->getResultDisplay($results, 'billing', $controller->getLayout());
                if($display == '') {
                    return;
                }
                $candidateCount = count($results->getCandidates());
                $name = $candidateCount > 0 && $outcome != Shipperhq_Validation_Model_Validator_Result::INVALID ? 'dialog-billing' : 'nonedialog-billing';
                $result['update_section'] = array(
                    'name' => $name, //'dialog-billing',
                    'html' => $validator->getJqueryHtml($name) .$display
                );

                if (Mage::helper('shipperhq_shipper')->isModuleEnabled('EcomDev_CheckItOut', 'ecomdev_checkitout/settings/active')) {
                    $currentResponse = $observer->getControllerAction()->getResponse()->getBody();
                    $unEncode = Mage::helper('core')->jsonDecode($currentResponse);
                }

                $unEncode['update_section'] = $result['update_section'];

                $observer->getControllerAction()->getResponse()->setBody(Mage::helper('core')->jsonEncode($unEncode));
            }
        }
    }

    /**
     *
     * Set the address type if known
     * @param $observer
     */
    private function saveShippingQuoteBefore($observer)
    {
        if(!Mage::helper('shipperhq_validation')->validateAtCheckout()) {
            return;
        }
        $request = $observer->getControllerAction()->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost('shipping', array());
            $alreadyValidated = array_key_exists('address_valid', $data) ? (string)$data['address_valid']: '';
            $destinationType = array_key_exists('destination_type', $data) ? (string)$data['destination_type']: null;
            Mage::unregister('Shipperhq_Destination_Type');
            Mage::register('Shipperhq_Destination_Type', $destinationType);
            $customerAddressId = $request->getPost('shipping_address_id', false);
            $selectedValidatedAddress = $data['updated_address_flag'];

            if (!empty($customerAddressId)) {
                $customerAddress = Mage::helper('shipperhq_validation')->getCustomerAddress($customerAddressId);
                if ($customerAddress->getId()) {
                    $data = $customerAddress->getData();
                    $customerAddress->setAddressValid($alreadyValidated);
                    if(!is_null($destinationType) && $destinationType != '') {
                        $customerAddress->setDestinationType($destinationType);
                    }
                    $customerAddress->save();

                }
            }
            elseif($selectedValidatedAddress != 0) {
                $customerAddress = Mage::helper('shipperhq_validation')->getCustomerAddress($selectedValidatedAddress);
                if ($customerAddress->getId()) {
                    $this->updateCustomerAddress($customerAddress, $data, $alreadyValidated);
                }
            }
            if (!Mage::helper('shipperhq_validation')->shouldRevalidate($alreadyValidated)) {
                return;
            }
            Mage::unregister('Shipperhq_Validation_Results');
            $validator = Mage::getModel('shipperhq_validation/validator');
            if($validator) {
                $results = $validator->validateAddress($data);
                if($results && $outcome = $results->getOutcome()) {
                    $data['address_valid'] = $outcome;
                    $request->setPost('shipping', $data);
                    Mage::register('Shipperhq_Validation_Results', $results, true);
                    $candidateCount = count($results->getCandidates());
                    if($candidateCount == 1 && $outcome == Shipperhq_Shipper_Model_Source_Validation_Result::VALID) {
                        $candidates = $results->getCandidates();
                        $destinationType = $candidates[0]['destination_type'];
                        Mage::unregister('Shipperhq_Destination_Type');
                        Mage::register('Shipperhq_Destination_Type', $destinationType);
                        if($customerAddressId && $customerAddress) {
                            if($destinationType != '') {
                                $customerAddress->setDestinationType($destinationType);
                            }
                            $customerAddress->setAddressValid($outcome);
                            $customerAddress->save();
                        }
                    }
                }

            }
        }

    }

    private function saveShippingQuoteAfter($observer)
    {
        if(!Mage::helper('shipperhq_validation')->validateAtCheckout()) {
            return;
        }

        $request = $observer->getControllerAction()->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost('shipping', array());

            $validator = Mage::getModel('shipperhq_validation/validator');
            $results = Mage::registry('Shipperhq_Validation_Results');
            if($results && $outcome = $results->getOutcome()) {
                $data['address_valid'] = $outcome;
                $request->setPost('shipping', $data);
                $controller = $observer->getEvent()->getData('controller_action');
                $controller->loadLayout('checkout_onepage_index');
                $display =  $validator->getResultDisplay($results, 'shipping', $controller->getLayout());
                $candidateCount = count($results->getCandidates());
                if($display == '') {
                    if($candidateCount == 1 && $outcome == Shipperhq_Shipper_Model_Source_Validation_Result::VALID) {
                        $candidates = $results->getCandidates();
                        $destinationType = $candidates[0]['destination_type'];
                        Mage::unregister('Shipperhq_Destination_Type');
                        Mage::register('Shipperhq_Destination_Type', $destinationType);
                    }
                    return;
                }
                $name = $candidateCount > 0 && $outcome != Shipperhq_Validation_Model_Validator_Result::INVALID ? 'dialog-shipping' : 'nonedialog-shipping';

                $result['update_section'] = array(
                    'name' => $name,
                    'html' => $validator->getJqueryHtml($name) .$display
                );

                if (Mage::helper('shipperhq_shipper')->isModuleEnabled('EcomDev_CheckItOut', 'ecomdev_checkitout/settings/active')) {
                    $currentResponse = $observer->getControllerAction()->getResponse()->getBody();
                    $unEncode = Mage::helper('core')->jsonDecode($currentResponse);
                }

                $unEncode['update_section'] = $result['update_section'];

                $observer->getControllerAction()->getResponse()->setBody(Mage::helper('core')->jsonEncode($unEncode));
            }

        }
    }

    /**
     *
     * Validate customer address
     * @param $observer
     */
    private function customerAddressFormPredispatch($observer)
    {
        if(Mage::helper('shipperhq_validation')->validateAtAddressBook()) {

            $request = $observer->getControllerAction()->getRequest();
            if ($request->isPost()) {
                $params = $request->getParams();
                if(!array_key_exists('address_valid', $params) || $params['address_valid'] == '0' || $params['address_valid'] == '') {
                    $validator = Mage::getModel('shipperhq_validation/validator');
                    if($validator) {
                        $results = $validator->validateAddress($params);
                        if($results && $outcome = $results->getOutcome()) {
                            $params['address_valid'] = $outcome;
                            $request->setParams($params);
                        }
                    }
                }
            }
        }
    }

    public function onestepcheckoutSaveBillingBefore($observer)
    {
        if(!Mage::helper('shipperhq_validation')->validateAtCheckout()) {
            return;
        }
        $request = $observer->getControllerAction()->getRequest();
        if ($request->isPost()) {
            $billingdata = $request->getPost('billing',array());
            if(isset($billingdata['use_for_shipping']) && $billingdata['use_for_shipping'] == 1) {
                $data = $billingdata;
            }
            else {
                $data = $request->getPost('shipping',array());
            }
            $destinationType = $data['destination_type'];
            Mage::unregister('Shipperhq_Destination_Type');
            Mage::register('Shipperhq_Destination_Type', $destinationType);
            $customerAddressId = array_key_exists('shipping_address_id', $data) ? (string)$data['shipping_address_id']: null;
            if (!empty($customerAddressId) && !is_null($destinationType) && $destinationType != '') {
                $customerAddress = Mage::helper('shipperhq_validation')->getCustomerAddress($customerAddressId);
                if ($customerAddress->getId()) {
                    $customerAddress->setDestinationType($destinationType)
                        ->save();

                }
            }
        }
    }

    private function updateCustomerAddress($address, $data, $validatedResult)
    {
        if($data['street'][1] != '') {
            $address->setStreet($data['street']);
        }
        else {
            $address->setStreet($data['street'][0]);
        }
        $address->setCity($data['city']);
        $regionId=$data['region_id'];
        if (is_numeric($regionId)) {
            $region = Mage::getModel('directory/region')->load($regionId);
            $regionName = $region->getName();
            $address->setRegionId($regionId);
            $address->setRegion($regionName);
        }
        $address->setPostcode($data['postcode']);
        $address->setCountryId($data['country_id']);
        $address->setAddressValid($validatedResult);
        if(isset($data['destination_type']) && $data['destination_type'] != '') {
            $address->setDestinationType($data['destination_type']);
        }
        $address->save();
    }


}


