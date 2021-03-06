<?php

class TM_FireCheckout_Model_Type_Standard
{
    /**
     * Checkout types: Checkout as Guest, Register, Logged In Customer
     */
    const METHOD_GUEST    = 'guest';
    const METHOD_REGISTER = 'register';
    const METHOD_CUSTOMER = 'customer';
//    const METHOD_GUEST_CUSTOMER = 'guest_customer';

    /**
     * Error message of "customer already exists"
     *
     * @var string
     */
    private $_customerEmailExistsMessage = '';

    /**
     * @var Mage_Customer_Model_Session
     */
    protected $_customerSession;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $_checkoutSession;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * @var Mage_Checkout_Helper_Data
     */
    protected $_helper;

    /**
     * Class constructor
     * Set customer already exists message
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('checkout');
        $this->_customerEmailExistsMessage = $this->_helper->__('There is already a customer registered using this email address. Please login using this email address or enter a different email address to register your account.');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_customerSession = Mage::getSingleton('customer/session');
    }

    /**
     * Get frontend checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return $this->_checkoutSession;
    }

    /**
     * Quote object getter
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->_quote === null) {
            return $this->_checkoutSession->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Declare checkout quote instance
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Get customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }


    /**
     * Retrieve shipping and billing addresses,
     * and boolean flag about their equality
     *
     * For the registered customer with available addresses returns
     * appropriate address.
     * For the Guest trying to detect country with geo-ip technology
     *
     * @return array
     */
    protected function _getDefaultAddress()
    {
        $result = array(
            'shipping' => array(
                'country_id'          => null,
                'city'                => null,
                'region_id'           => null,
                'postcode'            => null,
                'customer_address_id' => false
            ),
            'billing' => array(
                'country_id'          => null,
                'city'                => null,
                'region_id'           => null,
                'postcode'            => null,
                'customer_address_id' => false,
                'use_for_shipping'    => Mage::getStoreConfig('firecheckout/general/shipping_address_checkbox_state'),
                'register_account'    => 0
            )
        );
        if (($customer = Mage::getSingleton('customer/session')->getCustomer())
            && ($addresses = $customer->getAddresses())) {

            if (!$shippingAddress = $customer->getPrimaryShippingAddress()) {
                foreach ($addresses as $address) {
                    $shippingAddress = $address;
                    break;
                }
            }
            if (!$billingAddress = $customer->getPrimaryBillingAddress()) {
                foreach ($addresses as $address) {
                    $billingAddress = $address;
                    break;
                }
            }
            $result['shipping'] = $shippingAddress->getData();
            $result['shipping']['country_id']          = $shippingAddress->getCountryId();
            $result['shipping']['customer_address_id'] = $shippingAddress->getId();
            $result['billing'] = $billingAddress->getData();
            $result['billing']['country_id']           = $billingAddress->getCountryId();
            $result['billing']['customer_address_id']  = $billingAddress->getId();
            $result['billing']['use_for_shipping']     = $shippingAddress->getId() === $billingAddress->getId();
        } else if ($this->getQuote()->getShippingAddress()->getCountryId()) {
            // Estimated shipping cost from shopping cart
            $address = $this->getQuote()->getShippingAddress();
            $result['shipping'] = $address->getData();
            if (!$address->getSameAsBilling()) {
                $address = $this->getQuote()->getBillingAddress();
                $result['billing'] = $address->getData();
                $result['billing']['use_for_shipping'] = false;
            } else {
                $result['billing'] = $result['shipping'];
                $result['billing']['use_for_shipping'] = true;
            }
        } else {
            $remoteAddr = Mage::helper('core/http')->getRemoteAddr();
            try {
                $data = Mage::helper('firecheckout/geoip')->detect($remoteAddr);
                foreach ($data as $key => $value) {
                    $result['shipping'][$key] =
                        $result['billing'][$key] = $value;
                }
            } catch (Exception $e) {
                $this->_checkoutSession->addError($e->getMessage());
            }

            if (empty($result['shipping']['country_id'])
                || !Mage::getResourceModel('directory/country_collection')
                    ->addCountryCodeFilter($result['shipping']['country_id'])
                    ->loadByStore()
                    ->count()) {

                $result['shipping']['country_id'] =
                    $result['billing']['country_id'] = Mage::helper('core')->getDefaultCountry();
            }
        }

        // discount (free shipping rule) matters
        $result['billing']['register_account'] = $this->_canUseRegistrationMode();

        return $result;
    }

    /**
     * Check if registration checkbox is checked
     *
     * @return boolean
     */
    protected function _canUseRegistrationMode()
    {
        if (null !== Mage::app()->getRequest()->getParam('register')) {
            return true;
        }

        return in_array(
            Mage::getStoreConfig('firecheckout/general/registration_mode'),
            array('optional-checked', 'hidden', 'required')
        );
    }

    /**
     * @param object $method
     * @return boolean
     */
    protected function _canUsePaymentMethod($method)
    {
        if (!$method->canUseForCountry($this->getQuote()->getBillingAddress()->getCountry())) {
            return false;
        }

        $total = $this->getQuote()->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }

        if (Mage::helper('core')->isModuleOutputEnabled('Amasty_Methods')) {
            if (!Mage::helper('ammethods')->canUseMethod($method, 'payment')) {
                return false;
            }
        }

        return true;
    }

    protected function _canUseShippingMethod($method)
    {
        if (Mage::helper('core')->isModuleOutputEnabled('Amasty_Methods')) {
            $ammethods = Mage::helper('ammethods');
            if ($ammethods->isRestrictShippingMethod()
                && !$ammethods->canUseMethod($method, 'shipping')) {

                return false;
            }
        }

        // copied from Ophirah_Qquoteadv_Block_Checkout_Onepage_Shipping_Method_Available
        if (Mage::helper('core')->isModuleOutputEnabled('Ophirah_Qquoteadv')) {
            if (Mage::helper('qquoteadv')->isActiveConfirmMode(true) &&
                Mage::app()->getHelper('qquoteadv')->isSetQuoteShipPrice()) {

                return 'qquoteshiprate' === $method;
            } else {
                return 'qquoteshiprate' !== $method;
            }
        }

        return true;
    }

    /**
     * Set the default values at the start of payment process
     *
     * @return TM_FireCheckout_Model_Type_Standard
     */
    public function applyDefaults()
    {
        $addressInfo = $this->_getDefaultAddress();
        $this->saveBilling(
            $addressInfo['billing'],
            $addressInfo['billing']['customer_address_id'],
            false
        );
        if (!$addressInfo['billing']['use_for_shipping']) {
            $this->saveShipping(
                $addressInfo['shipping'],
                $addressInfo['shipping']['customer_address_id'],
                false
            );
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote = $this->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        // weight vs destination fix
        $weight = 0;
        foreach($quote->getAllVisibleItems() as $item) {
            $weight += ($item->getWeight() * $item->getQty()) ;
        }
        $shippingAddress->setFreeMethodWeight($weight)->setWeight($weight);

        $this->applyPaymentMethod();
        if (!$quote->isVirtual()) {
            $shippingAddress->collectTotals()->collectShippingRates()->save(); // premiumrate fix
            $this->applyShippingMethod();
        }
        // shipping method may affect the total in both sides (discount on using shipping address)
        $quote->collectTotals();

        $ajaxHelper = Mage::helper('firecheckout/ajax');
        if ($ajaxHelper->getIsShippingMethodDependsOn('total') && !$quote->isVirtual()) {
            // changing total by shipping price may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsTotalDependsOn('payment-method')) { // @todo && method is changed
            // recollect totals again because adding/removing payment
            // method may add/remove some discounts in the order

            // to recollect discount rules need to clear previous discount
            // descriptions and mark address as modified
            // see _canProcessRule in Mage_SalesRule_Model_Validator

            // this line makes subtotal calculated without tax, causing missing discount
            // without this line discount per payment method is not applied if
            // 'discountable' method is default one. FIXED by moving applyPayment method before applyShipping
            // $shippingAddress->setDiscountDescriptionArray(array())->isObjectNew(true);

            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        $quote->save();

        return $this;
    }

    /**
     * Update payment method information
     * Removes previously selected method if none is available,
     * set available if only one is available,
     * set previously selected payment,
     * set default from config if possible
     *
     * @param string $methodCode Default method code
     * @return TM_FireCheckout_Model_Type_Standard
     */
    public function applyPaymentMethod($methodCode = null)
    {
        if (false === $methodCode) {
            return $this->getQuote()->removePayment();
        }

        $store = $this->getQuote() ? $this->getQuote()->getStoreId() : null;
        $methods = Mage::helper('payment')->getStoreMethods($store, $this->getQuote());
        $availablePayments = array();
        foreach ($methods as $key => $method) {
            if (!$method || !$method->canUseCheckout()) {
                continue;
            }
            if ($this->_canUsePaymentMethod($method)) {
                $availablePayments[] = $method;
            }
        }

        $found = false;
        $count = count($availablePayments);
        if (1 === $count) {
            $methodCode = $availablePayments[0]->getCode();
            $found      = true;
        } elseif ($count) {
            if (!$methodCode) {
               $methodCode = $this->getQuote()->getPayment()->getMethod();
            }
            if ($methodCode) {
                foreach ($availablePayments as $payment) {
                    if ($methodCode == $payment->getCode()) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found || !$methodCode) {
                $methodCode = Mage::getStoreConfig('firecheckout/general/payment_method');
                foreach ($availablePayments as $payment) {
                    if ($methodCode == $payment->getCode()) {
                        $found = true;
                        break;
                    }
                }
            }
        }

        if (!$found) {
             $this->getQuote()->removePayment();
        } elseif ($methodCode) {
            $payment = $this->getQuote()->getPayment();
            $payment->setMethod($methodCode);
            $payment->setMethodInstance(null); // fix for billmate payments
            $method = $payment->getMethodInstance();
            try {
                $data = new Varien_Object(array('method' => $methodCode));
                $method->assignData($data);
            } catch (Exception $e) {
                // Adyen HPP extension fix
            }

            if ($this->getQuote()->isVirtual()) { // discount are looking for method inside address
                $this->getQuote()->getBillingAddress()->setPaymentMethod($methodCode);
            } else {
                $this->getQuote()->getShippingAddress()->setPaymentMethod($methodCode);
            }
        }

        return $this;
    }

    /**
     * Update shipping method information
     * Removes previously selected method if none is available,
     * set available if only one is available,
     * set previously selected payment,
     * set default from config if possible
     *
     * @param string $methodCode Default method code
     * @return TM_FireCheckout_Model_Type_Standard
     */
    public function applyShippingMethod($methodCode = null)
    {
        if (false === $methodCode) {
            return $this->getQuote()->getShippingAddress()->setShippingMethod(false);
        }
        $rates = Mage::getModel('sales/quote_address_rate')->getCollection()
            ->setAddressFilter($this->getQuote()->getShippingAddress()->getId())
            ->toArray();

        // unset error shipping methods. Like ups_error etc.
        $hideIfFree = Mage::getStoreConfigFlag('firecheckout/general/hide_shipping_if_free');
        foreach ($rates['items'] as $k => $rate) {
            if ('freeshipping' === $rate['method']
                && empty($rate['error_message'])
                && $hideIfFree) {

                $this->getQuote()->getShippingAddress()->setShippingMethod($rate['code']);
                return $this;
            }
            if ((empty($rate['method']) && $rate['code'] !== 'productmatrix_')
                || 'customshippingrate' == $rate['method']) {

                unset($rates['items'][$k]);
                continue;
            }

            if (!$this->_canUseShippingMethod($rate['method'])) {
                unset($rates['items'][$k]);
            }
        }
        reset($rates['items']);

        if ((!$count = count($rates['items']))) {
            $this->getQuote()->getShippingAddress()->setShippingMethod(false);
        } elseif (1 === $count && (!Mage::helper('firecheckout')->isOnecolumnMode() || $methodCode)) {
            // do not apply method for onecolumn mode, because shipping methods are hidden on initial page load
            $rate = current($rates['items']);
            $this->getQuote()->getShippingAddress()->setShippingMethod($rate['code']);
        } else {
            $found = false;
            if (!$methodCode) {
                $methodCode = $this->getQuote()->getShippingAddress()->getShippingMethod();
            }
            if ($methodCode) {
                foreach ($rates['items'] as $rate) {
                    if (is_array($methodCode) || $methodCode === $rate['code']) {
                        $this->getQuote()->getShippingAddress()->setShippingMethod($methodCode);
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found || !$methodCode) {
                $methodCodes = array(
                    Mage::getStoreConfig('firecheckout/general/shipping_method_code'),
                    Mage::getStoreConfig('firecheckout/general/shipping_method')
                );
                foreach ($methodCodes as $methodCode) {
                    if (!$methodCode) {
                        continue;
                    }
                    foreach ($rates['items'] as $rate) {
                        if ($methodCode === $rate['code']) {
                            $this->getQuote()->getShippingAddress()->setShippingMethod($methodCode);
                            $found = true;
                            break 2;
                        }
                    }
                }
            }
//            if (!$found) {
//                foreach ($rates['items'] as $rate) {
//                    $this->getQuote()->getShippingAddress()->setShippingMethod($rate['code']);
//                    $found = true;
//                    break;
//                }
//            }
        }
        return $this;
    }

    /**
     * Initialize quote state to be valid for one page checkout
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function initCheckout()
    {
        $checkout = $this->getCheckout();
        $customerSession = $this->getCustomerSession();

        $quoteSave = false;
        $collectTotals = false;

        /**
         * Reset multishipping flag before any manipulations with quote address
         * addAddress method for quote object related on this flag
         */
        if ($this->getQuote()->getIsMultiShipping()) {
            $this->getQuote()->setIsMultiShipping(false);
            $quoteSave = true;
        }

        // /**
        //  *  Reset customer balance
        //  */
        // if ($this->getQuote()->getUseCustomerBalance()) {
        //     $this->getQuote()->setUseCustomerBalance(false);
        //     $quoteSave = true;
        //     $collectTotals = true;
        // }
        // /**
        //  *  Reset reward points
        //  */
        // if ($this->getQuote()->getUseRewardPoints()) {
        //     $this->getQuote()->setUseRewardPoints(false);
        //     $quoteSave = true;
        //     $collectTotals = true;
        // }

        if ($collectTotals) {
            $this->getQuote()->collectTotals();
        }

        if ($quoteSave) {
            $this->getQuote()->save();
        }

        /*
        * want to load the correct customer information by assiging to address
        * instead of just loading from sales/quote_address
        */
        $customer = $customerSession->getCustomer();
        if ($customer) {
            if ($customer->getId()) {
                $this->getQuote()->setCustomer($customer);
            }
            // $this->getQuote()->assignCustomer($customer); // fixed reseting of selected address
        }
        return $this;
    }

    /**
     * Get quote checkout method
     *
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return self::METHOD_CUSTOMER;
        }
        if (!$this->getQuote()->getCheckoutMethod()) {
            if (Mage::helper('firecheckout')->isAllowedGuestCheckout()) {
                $this->getQuote()->setCheckoutMethod(self::METHOD_GUEST);
            } else {
                $this->getQuote()->setCheckoutMethod(self::METHOD_REGISTER);
            }
        }
        return $this->getQuote()->getCheckoutMethod();
    }

    /**
     * Specify checkout method
     *
     * @param   string $method
     * @return  array
     */
    public function saveCheckoutMethod($method)
    {
        if (empty($method)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }

        $this->getQuote()->setCheckoutMethod($method)->save();
        return array();
    }

    /**
     * Get customer address by identifier
     *
     * @param   int $addressId
     * @return  Mage_Customer_Model_Address
     */
    public function getAddress($addressId)
    {
        $address = Mage::getModel('customer/address')->load((int)$addressId);
        $address->explodeStreetAddress();
        if ($address->getRegionId()) {
            $address->setRegion($address->getRegionId());
        }
        return $address;
    }

    /**
     * Save billing and shipping information to quote
     *
     * @param  array  $data
     * @param  int  $customerAddressId
     * @param  boolean $validate
     * @param  string  $addressType
     * @return array
     */
    protected function _saveAddress($data, $customerAddressId, $validate = true, $addressType = 'billing')
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }

        if ($addressType === 'billing') {
            $address = $this->getQuote()->getBillingAddress();
        } else {
            $address = $this->getQuote()->getShippingAddress();
        }

        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
            ->setEntityType('customer_address')
            ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        if (!empty($customerAddressId)) {
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            if ($customerAddress->getId()) {
                if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
                    return array('error' => 1,
                        'message' => $this->_helper->__('Customer Address is not valid.')
                    );
                }
                $address->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);
                $addressForm->setEntity($address);
                if ($validate) {
                    $addressErrors = $addressForm->validateData($address->getData());
                    if ($addressErrors !== true) {

                        // Prepare form data to let customer to edit it at checkout page.
                        // Useful, when some attribute becomes required and it's
                        // missing in the form saved earlier
                        $addressData = $address->getData();
                        $formData = array();
                        foreach ($addressForm->getAttributes() as $attribute) {
                            $code = $attribute->getAttributeCode();
                            if (array_key_exists($code, $addressData)) {
                                $formData[$code] = $addressData[$code];
                            }
                        }

                        return array(
                            'error'     => 1,
                            'message'   => $addressErrors,
                            'address_update' => array(
                                'type' => $addressType,
                                'form_data' => $formData,
                                'invalid_fields' => $this->getInvalidAddressFields($address),
                            ),
                        );
                    }
                }
            }
        } else {
            $address->setCustomerAddressId(null);
            $addressForm->setEntity($address);
            // emulate request object
            $addressData = $addressForm->extractData($addressForm->prepareRequest($data));
            if ($validate) {
                $addressErrors = $addressForm->validateData($addressData);
                if ($addressErrors !== true) {
                    return array('error' => 1, 'message' => $addressErrors);
                }
            }
            $addressForm->compactData($addressData);
            //unset billing address attributes which were not shown in form
            foreach ($addressForm->getAttributes() as $attribute) {
                if (!isset($data[$attribute->getAttributeCode()])) {
                    $address->setData($attribute->getAttributeCode(), NULL);
                }
            }

            // Additional form data, not fetched by extractData (as it fetches only attributes)
            $address->setSaveInAddressBook(empty($data['save_in_address_book']) ? 0 : 1);
        }

        return array();
    }

    /**
     * Save billing address information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  array
     */
    public function saveBilling($data, $customerAddressId, $validate = true)
    {
        /* old code */
        if (!empty($data)) {
            if (isset($data['register_account']) && $data['register_account']) {
                $this->getQuote()->setCheckoutMethod(self::METHOD_REGISTER);
            } else if ($this->getCustomerSession()->isLoggedIn()) {
                $this->getQuote()->setCheckoutMethod(self::METHOD_CUSTOMER);
            } else {
                $this->getQuote()->setCheckoutMethod(self::METHOD_GUEST);
            }
        }
        /* eof old code */

        $errors = $this->_saveAddress($data, $customerAddressId, $validate, 'billing');
        if ($errors) {
            return $errors;
        }

        $address = $this->getQuote()->getBillingAddress();

        // set email for newly created user
        if (!$address->getEmail() && $this->getQuote()->getCustomerEmail()) {
            $address->setEmail($this->getQuote()->getCustomerEmail());
        }

        // validate billing address
        if ($validate && ($validateRes = $address->validate()) !== true) {
            return array('error' => 1, 'message' => $validateRes);
        }

        $address->implodeStreetAddress();

        if (true !== ($result = $this->_validateCustomerData($data, $validate))) {
            if ($validate) {
                return $result;
            }
        }

        if (isset($data['taxvat'])) { // fix for euvat extension
            $this->getQuote()->setCustomerTaxvat($data['taxvat']);
        }

        if ($validate
            && !$this->getQuote()->getCustomerId()
            && self::METHOD_REGISTER == $this->getQuote()->getCheckoutMethod()) {

            if ($this->_customerEmailExists($address->getEmail(), Mage::app()->getWebsite()->getId())) {
                return array('error' => 1, 'message' => $this->_customerEmailExistsMessage);
            }
        }

        if (!$this->getQuote()->isVirtual()) {
            /**
             * Billing address using options
             */
            $usingCase = isset($data['use_for_shipping']) ? (int)$data['use_for_shipping'] : 0;

            switch($usingCase) {
                case 0:
                    $shipping = $this->getQuote()->getShippingAddress();
                    $shipping->setSameAsBilling(0);
                    break;
                case 1:
                    $billing = clone $address;
                    $billing->unsAddressId()->unsAddressType();
                    $shipping = $this->getQuote()->getShippingAddress();
                    $shippingMethod = $shipping->getShippingMethod();

                    // Billing address properties that must be always copied to shipping address
                    $requiredBillingAttributes = array('customer_address_id');

                    // don't reset original shipping data, if it was not changed by customer
                    foreach ($shipping->getData() as $shippingKey => $shippingValue) {
                        if (!is_null($shippingValue)
                            && !is_null($billing->getData($shippingKey))
                            && !isset($data[$shippingKey])
                            && !in_array($shippingKey, $requiredBillingAttributes)
                        ) {
                            $billing->unsetData($shippingKey);
                        }
                    }
                    $shipping->addData($billing->getData())
                        ->setSameAsBilling(1)
                        ->setSaveInAddressBook(0)
                        ->setShippingMethod($shippingMethod)
                        ->setCollectShippingRates(true);
                    $this->getCheckout()->setStepData('shipping', 'complete', true);
                    break;
            }
        }


        /* old code */
        if ($validate && (true !== $result = $this->_processValidateCustomer($address))) {
            return $result;
        }
        /* eof old code */

        return array();
    }

    /**
     * Validate customer data and set some its data for further usage in quote
     * Will return either true or array with error messages
     *
     * @param array $data
     * @return true|array
     */
    protected function _validateCustomerData(array $data, $validate = true)
    {
        /* @var $customerForm Mage_Customer_Model_Form */
        $customerForm    = Mage::getModel('customer/form');
        $customerForm->setFormCode('checkout_register')
            ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        $quote = $this->getQuote();
        if ($quote->getCustomerId()) {
            $customer = $quote->getCustomer();
            $customerForm->setEntity($customer);
            $customerData = $quote->getCustomer()->getData();
        } else {
            /* @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer');
            $customerForm->setEntity($customer);
            $customerRequest = $customerForm->prepareRequest($data);
            $customerData = $customerForm->extractData($customerRequest);
        }

        $customerErrors = $customerForm->validateData($customerData);
        if ($validate && $customerErrors !== true) {
            return array(
                'error'     => -1,
                'message'   => implode(', ', $customerErrors)
            );
        }

        if ($quote->getCustomerId()) {
            return true;
        }

        $customerForm->compactData($customerData);

        if ($quote->getCheckoutMethod() == self::METHOD_REGISTER) {
            // set customer password
            $password = $customerRequest->getParam('customer_password');
            $registrationMode = Mage::getStoreConfig('firecheckout/general/registration_mode');
            if ('hidden' === $registrationMode || empty($password)) {
                $password = $customer->generatePassword();
                $customer->setPassword($password);
                $customer->setConfirmation($password);
                $customer->setPasswordConfirmation($password);
            } else {
                $customer->setPassword($customerRequest->getParam('customer_password'));
                $customer->setConfirmation($customerRequest->getParam('confirm_password'));
                $customer->setPasswordConfirmation($customerRequest->getParam('confirm_password'));
            }
        } else {
            // emulate customer password for guest
            $password = $customer->generatePassword();
            $customer->setPassword($password);
            $customer->setConfirmation($password);
            $customer->setPasswordConfirmation($password);
            // set NOT LOGGED IN group id explicitly,
            // otherwise copyFieldset('customer_account', 'to_quote') will fill it with default group id value
            $customer->setGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        }

        $result = $customer->validate();
        if ($validate && true !== $result && is_array($result)) {
            return array(
                'error'   => -1,
                'message' => implode(', ', $result)
            );
        }

        if ($quote->getCheckoutMethod() == self::METHOD_REGISTER) {
            // save customer encrypted password in quote
            $quote->setPasswordHash($customer->encryptPassword($customer->getPassword()));
        }

        // copy customer/guest email to address
        $quote->getBillingAddress()->setEmail($customer->getEmail());

        // copy customer data to quote
        $enteredTaxvat = $quote->getCustomerTaxvat();
        Mage::helper('core')->copyFieldset('customer_account', 'to_quote', $customer, $quote);
        if ($enteredTaxvat && !$quote->getCustomerTaxvat()) {
            $quote->setCustomerTaxvat($enteredTaxvat);
        }

        return true;
    }

    /**
     * Deprecated but used by firecheckout to validate the taxvat field
     *
     * @deprecated Need to rename to _processValidateTaxvat
     * @param Mage_Sales_Model_Quote_Address $address
     * @return true|array
     */
    protected function _processValidateCustomer(Mage_Sales_Model_Quote_Address $address)
    {
        // set customer tax/vat number for further usage
        $fields = Mage::getStoreConfig('firecheckout/taxvat/field_names');
        $fields = explode(',', $fields);

        $vatNumbers = array(
            'taxvat' => $this->getQuote()->getCustomerTaxvat(), // $taxvat = $address->getTaxvat();
            'vat_id' => $address->getVatId()
        );
        foreach ($vatNumbers as $fieldName => $value) {
            if (strlen($value) && in_array($fieldName, $fields)
                && Mage::getStoreConfig('firecheckout/taxvat/validate')) {

                $taxvatValidator = Mage::getModel('firecheckout/taxvat_validator');
                if (!$taxvatValidator->isValid($value, $address->getCountryId())) {
                    return array(
                        'error'   => -1,
                        'message' => $taxvatValidator->getMessage()
                    );
                }
            }
        }

        // set customer tax/vat number for further usage
        if ($address->getTaxnumber()) {
            $this->getQuote()->setCustomerTaxnumber($address->getTaxnumber());
        }

        return true;
    }

    /**
     * Save checkout shipping address
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  array
     */
    public function saveShipping($data, $customerAddressId, $validate = true)
    {
        $errors = $this->_saveAddress($data, $customerAddressId, $validate, 'shipping');
        if ($errors) {
            return $errors;
        }

        $address = $this->getQuote()->getShippingAddress();
        $address->setSameAsBilling(empty($data['same_as_billing']) ? 0 : 1);
        $address->implodeStreetAddress();
        $address->setCollectShippingRates(true);

        // validate billing address
        if ($validate && ($validateRes = $address->validate()) !== true) {
            return array('error' => 1, 'message' => $validateRes);
        }

        return array();
    }

    /**
     * Specify quote shipping method
     *
     * @param   string $shippingMethod
     * @return  array
     */
    public function saveShippingMethod($shippingMethod)
    {
        if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        $this->getQuote()->getShippingAddress()
            ->setShippingMethod($shippingMethod);
        $this->getQuote()->collectTotals()
            ->save();

        return array();
    }

    /**
     * Specify quote payment method
     *
     * @param   array $data
     * @return  array
     */
    public function savePayment($data)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        $quote = $this->getQuote();
        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        } else {
            $quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        }

        // shipping totals may be affected by payment method
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        if (@defined('Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT')) {
            $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
        }

        $payment = $quote->getPayment();
        $payment->setMethod(isset($data['method']) ? $data['method'] : false); // Magebuzz_Rewardpoint fix
        $payment->setMethodInstance(null); // fix for billmate payments

        // fix for third-party-modules. If collect totals was called before
        // save billing address - shipping description will stay empty
        $quote->setTotalsCollectedFlag(false);

        $payment->importData($data);

        $quote->save();

        // billpay integration
        Mage::dispatchEvent('billpay_after_save_payment', array(
            'data'=>$data,
            'useHTMLFormat'=>false,
            'expectedDaysTillShipping'=>0
        ));
        // billpay integration

        return array();
    }

    /**
     * Validate quote state to be integrated with one page checkout process
     */
    public function validate()
    {
        $helper = Mage::helper('checkout');
        $quote  = $this->getQuote();
        if ($quote->getIsMultiShipping()) {
            Mage::throwException($helper->__('Invalid checkout type.'));
        }

        if ($quote->getCheckoutMethod() == self::METHOD_GUEST && !Mage::helper('firecheckout')->isAllowedGuestCheckout()) {
            Mage::throwException($this->_helper->__('Sorry, guest checkout is not enabled. Please try again or contact store owner.'));
        }
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _prepareGuestQuote()
    {
        $quote = $this->getQuote();
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        return $this;
    }

    /**
     * Prepare quote for customer registration and customer order submit
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _prepareNewCustomerQuote()
    {
        $quote      = $this->getQuote();
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $quote->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $customerBilling = $billing->exportCustomerAddress();
        $customer->addAddress($customerBilling);
        $billing->setCustomerAddress($customerBilling);
        $customerBilling->setIsDefaultBilling(true);
        if ($shipping && !$shipping->getSameAsBilling()) {
            $customerShipping = $shipping->exportCustomerAddress();
            $customer->addAddress($customerShipping);
            $shipping->setCustomerAddress($customerShipping);
            $customerShipping->setIsDefaultShipping(true);
        } elseif ($shipping) {
            $customerBilling->setIsDefaultShipping(true);
        }

        if ($quote->getCustomerTaxnumber() && !$billing->getCustomerTaxnumber()) {
            $billing->setCustomerTaxnumber($quote->getCustomerTaxnumber());
        }

        Mage::helper('core')->copyFieldset('checkout_onepage_quote', 'to_customer', $quote, $customer);
        $customer->setPassword($customer->decryptPassword($quote->getPasswordHash()));
        $customer->setPasswordHash($customer->hashPassword($customer->getPassword()));
        $sessionValidatorData = $this->_checkoutSession->getData('_session_validator_data');
        if ($sessionValidatorData && isset($sessionValidatorData['session_expire_timestamp'])) {
            $passwordCreatedTime = $sessionValidatorData['session_expire_timestamp']
                - Mage::getSingleton('core/cookie')->getLifetime();
            $customer->setPasswordCreatedAt($passwordCreatedTime);
        }

        $quote->setCustomer($customer)
            ->setCustomerId(true);
        $quote->setPasswordHash('');
    }

    /**
     * Prepare quote for customer order submit
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _prepareCustomerQuote()
    {
        $quote      = $this->getQuote();
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $this->getCustomerSession()->getCustomer();
        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $customerBilling = $billing->exportCustomerAddress();
            $customer->addAddress($customerBilling);
            $billing->setCustomerAddress($customerBilling);
        }
        if ($shipping && !$shipping->getSameAsBilling() &&
            (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())) {
            $customerShipping = $shipping->exportCustomerAddress();
            $customer->addAddress($customerShipping);
            $shipping->setCustomerAddress($customerShipping);
        }

        if (isset($customerBilling) && !$customer->getDefaultBilling()) {
            $customerBilling->setIsDefaultBilling(true);
        }
        if ($shipping && isset($customerShipping) && !$customer->getDefaultShipping()) {
            $customerShipping->setIsDefaultShipping(true);
        } else if (isset($customerBilling) && !$customer->getDefaultShipping()) {
            $customerBilling->setIsDefaultShipping(true);
        }
        $quote->setCustomer($customer);
    }

    /**
     * Involve new customer to system
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _involveNewCustomer()
    {
        $customer = $this->getQuote()->getCustomer();
        if ($customer->isConfirmationRequired()) {
            $customer->sendNewAccountEmail(
                'confirmation',
                '',
                $this->getQuote()->getStoreId(),
                $customer->getPassword()
            );

            if ('hidden' !== Mage::getStoreConfig('firecheckout/general/registration_mode')) {
                $url = Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail());
                $this->getCheckout()->addSuccess(
                    Mage::helper('customer')->__(
                        'Account confirmation is required. Please, check your e-mail for confirmation link. To resend confirmation email please <a href="%s">click here</a>.',
                        $url
                    )
                );
            }
        } else {
            $customer->sendNewAccountEmail(
                'registered',
                '',
                $this->getQuote()->getStoreId(),
                $customer->getPassword()
            );
            $this->getCustomerSession()->loginById($customer->getId());
        }
        return $this;
    }

    /**
     * Create order based on checkout type. Create customer if necessary.
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function saveOrder()
    {
        $this->validate();
        $isNewCustomer = false;
        switch ($this->getCheckoutMethod()) {
            case self::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case self::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        $service = Mage::getModel('sales/service_quote', $this->getQuote());
        $service->submitAll();

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->_checkoutSession->setLastQuoteId($this->getQuote()->getId())
            ->setLastSuccessQuoteId($this->getQuote()->getId())
            ->clearHelperData();

        $order = $service->getOrder();
        if ($order) {
            Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                array('order'=>$order, 'quote'=>$this->getQuote()));

            /**
             * a flag to set that there will be redirect to third party after confirmation
             * eg: paypal standard ipn
             */
            $redirectUrl = $this->getQuote()->getPayment()->getOrderPlaceRedirectUrl();
            /**
             * we only want to send to customer about new order when there is no redirect to third party
             */
            $canSendNewEmailFlag = true;
            if (version_compare(Mage::helper('firecheckout')->getMagentoVersion(), '1.5.0.0', '>=')) {
                $canSendNewEmailFlag = $order->getCanSendNewEmailFlag();
            }
            if (!$redirectUrl && $canSendNewEmailFlag) {
                try {
                    if (method_exists($order, 'queueNewOrderEmail')) {
                        $order->queueNewOrderEmail();
                    } else {
                        $order->sendNewOrderEmail();
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            // add order information to the session
            $this->_checkoutSession->setLastOrderId($order->getId())
                ->setRedirectUrl($redirectUrl)
                ->setLastRealOrderId($order->getIncrementId());

            // as well a billing agreement can be created
            $agreement = $order->getPayment()->getBillingAgreement();
            if ($agreement) {
                $this->_checkoutSession->setLastBillingAgreementId($agreement->getId());
            }
        }

        // add recurring profiles information to the session
        $profiles = $service->getRecurringPaymentProfiles();
        if ($profiles) {
            $ids = array();
            foreach ($profiles as $profile) {
                $ids[] = $profile->getId();
            }
            $this->_checkoutSession->setLastRecurringProfileIds($ids);
            // TODO: send recurring profile emails
        }

        Mage::dispatchEvent(
            'checkout_submit_all_after',
            array('order' => $order, 'quote' => $this->getQuote(), 'recurring_profiles' => $profiles)
        );

        return $this;
    }

    /**
     * Validate quote state to be able submited from one page checkout page
     *
     * @deprecated after 1.4 - service model doing quote validation
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function validateOrder()
    {
        if ($this->getQuote()->getIsMultiShipping()) {
            Mage::throwException(Mage::helper('checkout')->__('Invalid checkout type.'));
        }

        if (!$this->getQuote()->isVirtual()) {
            $address = $this->getQuote()->getShippingAddress();
            $addressValidation = $address->validate();
            if ($addressValidation !== true) {
                Mage::throwException(Mage::helper('checkout')->__('Please check shipping address information.'));
            }
            $method= $address->getShippingMethod();
            $rate  = $address->getShippingRateByCode($method);
            if (!$this->getQuote()->isVirtual() && (!$method || !$rate)) {
                Mage::throwException(Mage::helper('checkout')->__('Please specify shipping method.'));
            }
        }

        $addressValidation = $this->getQuote()->getBillingAddress()->validate();
        if ($addressValidation !== true) {
            Mage::throwException(Mage::helper('checkout')->__('Please check billing address information.'));
        }

        if (!($this->getQuote()->getPayment()->getMethod())) {
            Mage::throwException(Mage::helper('checkout')->__('Please select valid payment method.'));
        }
    }

    /**
     * Check if customer email exists
     *
     * @param string $email
     * @param int $websiteId
     * @return false|Mage_Customer_Model_Customer
     */
    protected function _customerEmailExists($email, $websiteId = null)
    {
        $customer = Mage::getModel('customer/customer');
        if ($websiteId) {
            $customer->setWebsiteId($websiteId);
        }
        $customer->loadByEmail($email);
        if ($customer->getId()) {
            return $customer;
        }
        return false;
    }

    /**
     * Get last order increment id by order id
     *
     * @return string
     */
    public function getLastOrderId()
    {
        $lastId  = $this->getCheckout()->getLastOrderId();
        $orderId = false;
        if ($lastId) {
            $order = Mage::getModel('sales/order');
            $order->load($lastId);
            $orderId = $order->getIncrementId();
        }
        return $orderId;
    }

    /**
     * @param  [type] $address [description]
     * @return [type]          [description]
     * @deprecated since 4.2.3
     */
    public function validateAddress($address)
    {
        return true;
    }

    public function registerCustomerIfRequested()
    {
        if (self::METHOD_REGISTER != $this->getCheckoutMethod()) {
            return;
        }
        $this->_prepareNewCustomerQuote();
        $this->getQuote()->getCustomer()->save();
        $this->_involveNewCustomer();
    }

    /**
     * @param array $data
     * date => string[optional]
     * time => string[optional]
     */
    public function saveDeliveryDate(array $data, $validate = true)
    {
        $quote = $this->getQuote();
        $quote->setFirecheckoutDeliveryDate(null);
        $quote->setFirecheckoutDeliveryTimerange(null);

        $shippingMethod = $this->getQuote()->getShippingAddress()->getShippingMethod();
        /**
         * @var TM_FireCheckout_Helper_Deliverydate
         */
        $helper = Mage::helper('firecheckout/deliverydate');
        if (!$helper->canUseDeliveryDate($shippingMethod)) {
            return;
        }

        // validate the date for weekend and excluded days
        if (!empty($data['date'])) {
            try {
                // $date = new Zend_Date($data['date'], Mage::app()->getLocale()->getDateFormat());
                // $date = new Zend_Date($data['date'], Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $date = Mage::app()->getLocale()->date(
                    $data['date'],
                    Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
                    null,
                    false
                );
            } catch (Zend_Date_Exception $e) {
                return array(
                    'message' => Mage::helper('firecheckout')->__('Cannot parse delivery date. Following format expected: %s', Mage::app()->getLocale()->getDateFormat())
                );
            }

            if ($validate && !$helper->isValidDate($date)) {
                return array(
                    'message' => Mage::helper('firecheckout')->__('We cannot deliver the package at the selected date. Please select another date for the delivery')
                );
            }
            $quote->setFirecheckoutDeliveryDate($date->toString('yyyy-MM-dd'));
        }

        // validate time for valid range
        if (!empty($data['time'])) {
            if ($validate && !$helper->isValidTimeRange($data['time'])) {
                return array(
                    'message' => Mage::helper('firecheckout')->__('We cannot deliver the package at the selected time. Please select another time for the delivery')
                );
            }
            $quote->setFirecheckoutDeliveryTimerange($data['time']);
        }
    }

    public function getCustomerEmailExistsMessage()
    {
        return $this->_customerEmailExistsMessage;
    }

    public function getInvalidAddressFields($address)
    {
        $addressFormFirecheckout = Mage::getModel('firecheckout/customer_form');
        $addressFormFirecheckout->setFormCode('customer_address_edit')
            ->setEntityType('customer_address')
            ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax())
            ->setEntity($address);

        return $addressFormFirecheckout->getInvalidFields($address->getData());
    }
}
