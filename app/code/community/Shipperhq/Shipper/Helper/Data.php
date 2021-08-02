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
class Shipperhq_Shipper_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected static $_debug;
    protected static $_showTransId;
    protected static $_dateFormat;
    protected static $_zendDateFormat;
    protected static $_datepickerFormat;
    protected static $_shortDateFormat;
    protected static $_wsTimeout;
    protected $_prodAttributes;
    protected $_baseCurrencyRate;

    /**
     * @var Shipperhq_Shipper_Model_Storage_Manager
     */
    protected $_storageManager;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    public $magentoCarrierCodes =
        array(
            'ups'        => 'ups',
            'fedEx'      => 'fedex',
            'usps'       => 'usps',
            'dhl'        => 'dhl',
            'dhlint'     => 'dhlint',
            'upsFreight' => 'upsfreight'
        );

    CONST CALENDAR_DATE_OPTION = 'calendar';
    CONST DELIVERY_DATE_OPTION = 'delivery_date';
    CONST TIME_IN_TRANSIT = 'time_in_transit';
    CONST SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH = 'carriers/shipper/carriergroup_describer';
    CONST SHIPPERHQ_SHIPPER_ALLOWED_METHODS_PATH = 'carriers/shipper/allowed_methods';
    CONST SHIPPERHQ_LAST_SYNC = 'carriers/shipper/last_sync';
    CONST SHIPPERHQ_INVALID_CREDENTIALS_SUPPLIED = 'carriers/shipper/invalid_credentials_supplied';

    /**
     * Check is module exists and enabled in global config.
     *
     * @param $moduleName
     * @param null $enabledLocation
     * @return bool
     */
    public function isModuleEnabled($moduleName = null,$enabledLocation=null)
    {
        if ($moduleName === null) {
            $moduleName = $this->_getModuleName();
        }

        if (!Mage::getConfig()->getNode('modules/' . $moduleName)) {
            return false;
        }

        $isActive = Mage::getConfig()->getNode('modules/' . $moduleName . '/active');
        if (!$isActive || !in_array((string)$isActive, array('true', '1'))) {
            return false;
        }

        if ($enabledLocation === null) {
            return true;
        }

        if (!Mage::getStoreConfig($enabledLocation)) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isDebug()
    {
        if (self::$_debug == NULL) {
            self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Shipper');
        }
        return self::$_debug;
    }


    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isTransactionIdEnabled()
    {
        if (self::$_showTransId == NULL) {
            self::$_showTransId = Mage::getStoreConfigFlag('carriers/shipper/display_transaction');
        }
        return self::$_showTransId;

    }

    public function getTransactionId()
    {
        $id = Mage::registry('shipperhq_transaction');
        return $id;
    }

    /**
     * Returns an instance of storage manager
     *
     * @return Shipperhq_Shipper_Model_Storage_Manager
     */
    public function storageManager()
    {
        if ($this->_storageManager === null) {
            $this->_storageManager = Mage::getModel('shipperhq_shipper/storage_manager');
        }

        return $this->_storageManager;
    }

    /**
     * Returns a storage for a quote
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return Shipperhq_Shipper_Model_Storage|bool
     */
    public function getQuoteStorage($quote = null)
    {
        if ($quote === null) {
            $quote = $this->getQuote();
        }

        return $this->storageManager()->findByQuote($quote);
    }

    public function chooseCarrierAndProcess($carrierRate, $carrierGroupId = null, $carrierGroupDetail = null, $isSplit = false)
    {
        $carrierCode = $carrierRate->carrierCode;
        $sort = isset($carrierRate->sortOrder) ? $carrierRate->sortOrder : false;
        $this->_dynamicCarrierConfig($carrierCode, $carrierRate->carrierType, $carrierRate->carrierTitle, $sort);

        $isCalendar = false;
        $calendarDetails = (array)$carrierRate->calendarDetails;
        if(isset($carrierRate->dateOption) && $carrierRate->dateOption == self::CALENDAR_DATE_OPTION) {
            $isCalendar = true;
        }
        elseif (!empty($calendarDetails) && !isset($carrierRate->dateOption)) {
            //backwards compatibility
            $isCalendar  = true;
        }

        $this->populateCarrierLevelDetails((array)$carrierRate, $carrierGroupDetail);
        if ($this->isModuleEnabled('Shipperhq_Pickup') && Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($carrierRate->carrierType)) {
            if (!Mage::registry('pickup_carrier')) {
                $model = Mage::getModel('shipperhq_pickup/carrier_storepickup');
                Mage::register('pickup_carrier', $model);
            }
            return Mage::registry('pickup_carrier')->extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit);
        }
        else if ($this->isModuleEnabled('Shipperhq_Calendar') && $isCalendar) {
            if (!Mage::registry('calendar_carrier')) {
                $model = Mage::getModel('shipperhq_calendar/carrier_calendar');
                Mage::register('calendar_carrier', $model);
            }
            return Mage::registry('calendar_carrier')->extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit);
        }
        //Always process rates with standard if not already done
        if (!Mage::registry('shipper_carrier')) {
            $model = Mage::getModel('shipperhq_shipper/carrier_shipper');
            Mage::register('shipper_carrier', $model);
        }
        return Mage::registry('shipper_carrier')->extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit);
    }

    public function populateCarrierLevelDetails($carrierRate, &$carrierGroupDetail)
    {

        $carrierGroupDetail['carrierType'] = $carrierRate['carrierType'];
        $carrierGroupDetail['carrierTitle'] = $carrierRate['carrierTitle'];
        $carrierGroupDetail['carrier_code'] = $carrierRate['carrierCode'];
        $carrierGroupDetail['carrierName'] = $carrierRate['carrierName'];
        $shippingAddress = $this->getQuote()->getShippingAddress();

        $cgId = array_key_exists('carrierGroupId', $carrierGroupDetail) ? $carrierGroupDetail['carrierGroupId'] : null;

        $this->extractPackages($carrierRate, $carrierGroupDetail);
    }

    public function isPackageBreakdownDisplayEnabled()
    {
        return Mage::helper('wsalogger')->isDebugError();
    }

    public function loadSessionPackagesByCarrier($addressId, $carrierGroupId, $carrierCode, $methodCode)
    {
        $sessionPackages = $this->getQuoteStorage()->getShipperhqPackages();
        $shippingMethod = $carrierCode .'_' .$methodCode;
        $carrierPackages = isset($sessionPackages[$addressId]) && isset($sessionPackages[$addressId][$carrierGroupId]) ?
            $sessionPackages[$addressId][$carrierGroupId] : array();
        if(isset($carrierPackages[$shippingMethod])) {
            return $carrierPackages[$shippingMethod];
        }
        elseif (isset($carrierPackages[$carrierCode])) {
            return $carrierPackages[$carrierCode];
        }

        return array();

    }

    protected function extractPackages($carrierRate, &$carrierGroupDetail)
    {
        if(!$this->getQuote()->getShippingAddress()->getAddressId()) {
            return;
        }
        $shippingAddress = $this->getQuote()->getShippingAddress();
        $cgId = array_key_exists('carrierGroupId', $carrierGroupDetail) ? $carrierGroupDetail['carrierGroupId'] : null;

        $sessionPackages = $this->getQuoteStorage()->getShipperhqPackages();
        $carrierPackages = isset($sessionPackages[$shippingAddress->getAddressId()]) && isset($sessionPackages[$shippingAddress->getAddressId()][$cgId]) ?
            $sessionPackages[$shippingAddress->getAddressId()][$cgId] : array();

        //store packages
        if(array_key_exists('shipments', $carrierRate) && $carrierRate['shipments'] != null) {

            $mapping = $this->getPackagesMapping();
            $standardData = array('address_id' => $shippingAddress->getAddressId(),
                'carrier_group_id' => $cgId,
                'carrier_code' =>  $carrierRate['carrierCode']);
            $saveThese = array();
            foreach($carrierRate['shipments'] as $shipment) {
                $data = array_merge($standardData, Mage::helper('shipperhq_shipper/mapper')->map($mapping,(array)$shipment));
                $saveThese[] = $data;
            }
            if(count($saveThese) > 0) {
                $carrierPackages[$standardData['carrier_code']] = $saveThese;
            }
        }

        if(array_key_exists('rates', $carrierRate) && $carrierRate['rates'] != null) {
            foreach($carrierRate['rates'] as $rate) {
                $cgId = array_key_exists('carrierGroupId', $carrierGroupDetail) ? $carrierGroupDetail['carrierGroupId'] : null;
                $mapping = $this->getPackagesMapping();
                $standardData = array('address_id' => $shippingAddress->getAddressId(),
                    'carrier_group_id' => $cgId,
                    'carrier_code' =>  $carrierRate['carrierCode'].'_'.$rate->code);
                if(!isset($rate->shipments)) {
                    continue;
                }
                if(!is_null($standardData['address_id'])) {
                    $saveThese = array();
                    foreach($rate->shipments as $shipment) {
                        $data = array_merge($standardData, Mage::helper('shipperhq_shipper/mapper')->map($mapping, (array)$shipment));
                        $saveThese[] = $data;
                    }
                    if(count($saveThese) > 0) {
                        $carrierPackages[$standardData['carrier_code']] = $saveThese;
                    }
                }
            }
        }
        $sessionPackages[$shippingAddress->getAddressId()][$cgId] = $carrierPackages;
        $this->getQuoteStorage()->setShipperhqPackages($sessionPackages);
    }

    /**
     * @param $addressId
     * @param $carrierGroupId
     * @param $carrier_code
     * @deprecated SHQ16-1722 - Now stored against the session until order is placed
     */
    protected function cleanUpPackages($addressId, $carrierGroupId, $carrier_code)
    {
        try{
            $packages  = Mage::getModel('shipperhq_shipper/quote_packages')->loadByCarrier($addressId, $carrierGroupId, $carrier_code);
            foreach($packages as $old)
            {
                $old->delete();
            }
        }
        catch (Exception $e) {
            $result = false;
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper',
                    'Unable to remove existing packages', $e->getMessage());
            }
        }

    }

    protected function getPackagesMapping()
    {
        return array(
            'package_name' => 'name',
            'length' => 'length',
            'width' => 'width',
            'height' => 'height',
            'weight' => 'weight',
            'surcharge_price' => 'surchargePrice',
            'declared_value' => 'declaredValue',
            'volume' => 'volume',
            'items'         => 'boxedItems'
        );
    }

    public function getPackageBreakdown($carrierGroupId, $carrier_code)
    {
        if($this->isPackageBreakdownDisplayEnabled()) {
             $packages  = $this->loadSessionPackagesByCarrier(
                 $this->getQuote()->getShippingAddress()->getAddressId(),$carrierGroupId, $carrier_code, '');
            return $this->getPackageBreakdownText($packages);
        }

    }

    public function getPackageBreakdownText($packages, $carrierGroupName = false) {
        $boxText = '';
        $count = 1;
        if($carrierGroupName) {
            $boxText .= $carrierGroupName .': ';
        }
        foreach ($packages as $key=>$box)
        {
            $boxText .= Mage::helper('shipperhq_shipper')->__('Package').' #'.($count++);

            if ($box!=null) {
                $boxText .= ' Box name: ' .$box['package_name'];
                $boxText .= ' : ' . $box['length'];
                $boxText .= 'x' . $box['width'] ;
                $boxText .= 'x'. $box['height'] ;
                $boxText .= ': W='.$box['weight'] . ':' ;
                $boxText .= ' Value='.$box['declared_value']. ':';
                if(isset($box['volume'])) {
                    $boxText .= ' Volume=' .$box['volume'].':';
                }
                $boxText .= $this->getProductBreakdownText($box);
            }
            $boxText .= '<br />';
        }
        return $boxText;
    }

    public function getProductBreakdownText($box) {
        $productText = '';
        $weightUnit = $this->getGlobalSetting('weightUnit');
        if(!$weightUnit) {
            $weightUnit = '';
        }
        if (array_key_exists('items',$box)  || (is_object($box) && !is_null($box->getItems()))) {
            if (is_array($box['items'])) {
                foreach ($box['items'] as $item) {
                    $productText .= ' SKU=' .$item['qtyPacked'] .' * '.$item['sku'] .' ' .$item['weightPacked'] .$weightUnit;
                    if(isset($item['indVolume'])) {
                        $productText .= ': Individual Volume =' .$item['indVolume'].':';
                    }
                    if(isset($item['volumePacked'])) {
                        $productText .= ': Volume Packed =' .$item['volumePacked'].':';
                    }
                    $productText .=';  ';
                }
            } else {
                $productText = $box['items'];
            }
        }
        return $productText;
    }


    public function populateRateLevelDetails($rate, &$carrierGroupDetail, $currencyConversionRate)
    {
        $carrierGroupDetail['methodTitle'] = $rate['name'];
        $carrierGroupDetail['price'] = (float)$rate['totalCharges']*$currencyConversionRate;
        $carrierGroupDetail['cost'] = (float)$rate['shippingPrice']*$currencyConversionRate;
        $carrierGroupDetail['code'] = $rate['code'];
        if(isset($rate['selectedOptions'])) {
            $selectedOptions =  (array)$rate['selectedOptions'];
            if(isset($selectedOptions['options'])) {
                foreach($selectedOptions['options'] as $option) {
                    $thisOption =(array)$option;
                    if(isset($thisOption['name'])) {
                        $carrierGroupDetail[$thisOption['name']] = $thisOption['value'];
                    }
                }
            }
        }
    }

    public function getBaseCurrencyRate($code)
    {
        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
        if (!in_array($code, $allowedCurrencies)) {
            return false;
        }
        $storeId = $this->getQuote()->getStoreId();
        $baseCurrencyCode = $storeId ? Mage::app()->getStore($storeId)->getBaseCurrencyCode() :
            Mage::app()->getStore()->getBaseCurrency()->getCode();

        if (!$this->_baseCurrencyRate) {
            $this->_baseCurrencyRate = Mage::getModel('directory/currency')
                ->load($code)
                ->getAnyRate($baseCurrencyCode);
        }

        return $this->_baseCurrencyRate > 0 ? $this->_baseCurrencyRate : false;

    }
    public function setStandardShipperResponseType()
    {
        $shipping = $this->getQuote()->getShippingAddress();
        if($shipping->getSplitRates()) {
            $shipping->setSplitRates(0);
        }
    }

    public function isCheckout()
    {

        $shipping = $this->getQuote()->getShippingAddress();
        $isCheckout =  $shipping->getIsCheckout();
        if($this->getQuote()->getIsMultiShipping()) {
            return true;
        }
        return $isCheckout;
    }

    public function isMultiAddressCheckout()
    {
        return $this->getQuote()->getIsMultiShipping();
    }

    /**
     * Determine template to use for checkout shipping method display
     * Used during layout updates from Frontend module
     *
     * @return string
     */
    public function getAvailableTemplate()
    {
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper')) {
            return 'shipperhq/checkout/onepage/shipping_method/available.phtml';
        }
        return 'checkout/onepage/shipping_method/available.phtml';
    }

    /**
     * Set template for multiaddress checkout if enabled
     *
     * @return string
     */
    public function getMultiAddressTemplate()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            return 'shipperhq/checkout/multishipping/shipping.phtml';
        }
        return 'checkout/multishipping/shipping.phtml';
    }

    /**
     * Set template for Multi Address Overview if enabled
     *
     * @return string
     */
    public function getMultiAddressOverviewTemplate()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            return 'shipperhq/checkout/multishipping/overview.phtml';
        }
        return "checkout/multishipping/overview.phtml";
    }
    /*
     * Set template for onestepcheckout if enabled
     *
     * @return string
     */
    public function getOnestepcheckoutShippingTemplate()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            if(Mage::getStoreConfig('onestepcheckout/general/condense_shipping_methods')) {
                return 'shipperhq/checkout/onestepcheckout/shipping_method_osc_shq.phtml';
            }
            else {
                return 'shipperhq/checkout/onestepcheckout/shipping_method_osc_shq_radio.phtml';
            }
        }
        return 'onestepcheckout/shipping_method.phtml';
    }

    /**
     * Retrieve url for getting allowed methods
     * @return string
     */
    public function getAllowedMethodGatewayUrl()
    {
        return $this->_getGatewayUrl().'allowed_methods';
    }

    /**
     * Retrieve url for getting shipping rates
     * @return string
     */
    public function getRateGatewayUrl()
    {
        return  $this->_getGatewayUrl().'rates';

    }

    /**
     * Retrieve url for getting shipping rates
     * @return string
     */
    public function getValidationGatewayUrl()
    {
        return  $this->_getGatewayUrl().'address/validation';

    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getAttributeGatewayUrl()
    {
        return $this->_getGatewayUrl().'attributes/get';
    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getCheckSynchronizedUrl()
    {
        return $this->_getGatewayUrl().'attributes/check';
    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getSetSynchronizedUrl()
    {
        return $this->_getGatewayUrl().'attributes/set/updated';
    }

    /*
     * *Retrieve url for reserving order details
     */
    public function getReserveOrderGatewayUrl()
    {
        return $this->_getGatewayUrl().'order/reserve';
    }

    /*
     * *Retrieve url for confirming order
     */
    public function getConfirmOrderGatewayUrl()
    {
        return $this->_getGatewayUrl().'order/confirm';
    }

    /*
     * *Retrieve url for creating shipment
     */
    public function getCreateShipmentGatewayUrl()
    {
        return $this->_getGatewayUrl().'shipment/create';
    }

    /*
     * *Retrieve url for retrieving shipment add-on details
     */
    public function getShipmentAddonGatewayUrl()
    {
        return $this->_getGatewayUrl().'shipment/addon';
    }

    /*
     * Retrieve configured timeout for webservice
     */
    public function getWebserviceTimeout()
    {
        if (self::$_wsTimeout==NULL) {
            $timeout =  Mage::getStoreConfig('carriers/shipper/ws_timeout');
            if(!is_numeric($timeout)) {
                $timeout = 30;
            }
            self::$_wsTimeout = $timeout;
        }
        return self::$_wsTimeout;
    }

    /**
     * Saves the carrier title to core_config_data
     * Need to do this as doesnt read from the shipping rate quote table!
     * @param $carrierCode
     * @param $carrierTitle
     */
    public function saveCarrierTitle($carrierCode,$carrierTitle)
    {
        $this->saveConfig('carriers/'.$carrierCode.'/title',$carrierTitle);
    }

    /**
     * Save config value to db
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     * @return $this
     */
    public function saveConfig($path, $value, $scope = 'default', $scopeId = 0, $refreshRequired = true)
    {
        if (Mage::getStoreConfig($path) != $value) {
            Mage::getConfig()->saveConfig(rtrim($path, '/'), $value, $scope, $scopeId);
            if($refreshRequired) {
                Mage::helper('shipperhq_shipper')->getQuoteStorage()->setConfigUpdated(true);
            }
        }
    }

    public function encodeShippingDetails($shippingDetails)
    {
        return Zend_Json::encode($shippingDetails);
    }

    public function decodeShippingDetails($shippingDetailsEnc)
    {
        $decoded = array();
        if(!is_null($shippingDetailsEnc) && $shippingDetailsEnc != '') {
            $decoded = Zend_Json::decode($shippingDetailsEnc);
        }
        return $decoded;
    }

    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->_quote !== null) {
            return $this->_quote;
        }
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if(!$quote->getId()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }
        return $quote;

    }

    /**
     * Overrides quote model
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return $this
     */
    public function setQuote($quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    public function getGlobalSetting($code)
    {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if(!is_null($globals) && array_key_exists($code, $globals)
            && $globals[$code] != '') {
            return $globals[$code];
        }
        return false;
    }

    /*
     * Retrieve global settings saved to session
     *
     * @return array
     */
    public function getGlobalSettings()
    {
        return $this->getQuoteStorage($this->getQuote())->getShipperGlobal();
    }

    /**
     * Get a date format from global settings
     *
     * @param string $date
     * @return bool|string
     */
    public static function getDateFormat()
    {
        if (self::$_dateFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if( !is_null($globals) && array_key_exists('preferredLocale', $globals) && $globals['preferredLocale'] != '') {
                if($globals['preferredLocale'] == 'en-US') {
                    $rawDateFormat = 'mm/dd/yyyy';
                }
                else {
                    $rawDateFormat = 'dd-mm-yyyy';
                }
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_dateFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('date_format', $rawDateFormat);
        }
        return self::$_dateFormat;
    }

    /**
     * Get a date format in Zend format
     *
     * @param string $date
     * @return bool|string
     */
    public static function getZendDateFormat()
    {
        if (self::$_zendDateFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if( !is_null($globals) && array_key_exists('preferredLocale', $globals) && $globals['preferredLocale'] != '') {
                if($globals['preferredLocale'] == 'en-US') {
                    $rawDateFormat = 'mm/dd/yyyy';
                }
                else {
                    $rawDateFormat = 'dd-mm-yyyy';
                }
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_zendDateFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('zend_date_format', $rawDateFormat);
        }
        return self::$_zendDateFormat;
    }

    /**
     * Get a date format for calendar widget display
     *
     * @param string $date
     * @return bool|string
     */
    public static function getDatepickerFormat()
    {
        if (self::$_datepickerFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if( !is_null($globals) && array_key_exists('preferredLocale', $globals) && $globals['preferredLocale'] != '') {
                if($globals['preferredLocale'] == 'en-US') {
                    $rawDateFormat = 'mm/dd/yyyy';
                }
                else {
                    $rawDateFormat = 'dd-mm-yyyy';
                }
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_datepickerFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('datepicker_format', $rawDateFormat);
        }
        return self::$_datepickerFormat;
    }

    /**
     * Get a date format in short format
     *
     * @param string $date
     * @return bool|string
     */
    public static function getShortDateFormat()
    {
        if (self::$_shortDateFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if(!is_null($globals) && array_key_exists('dateFormat', $globals) && $globals['dateFormat'] != '') {
                $rawDateFormat = $globals['dateFormat'];
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_shortDateFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('short_date_format', $rawDateFormat);
        }
        return self::$_shortDateFormat;
    }

    public function showPickupConfirm()
    {
        return $this->getGlobalSetting('calendarConfirm');
    }

    public function isPickupRate($address, $shippingMethodChosen)
    {
        if(!$this->isModuleEnabled('Shipperhq_Pickup')) {
            return false;
        }
        $rate = $address->getShippingRateByCode($shippingMethodChosen);
        if($rate &&
            $this->isModuleEnabled('Shipperhq_Pickup') &&
            Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($rate->getCarrierType())) {
                return $rate->getCarrier();
        }
        return false;
    }

    /*
    *
    */
    public function getOurCarrierCode()
    {
        return Mage::getModel('shipperhq_shipper/carrier_shipper')->getCarrierCode();
    }

    public function mapToMagentoCarrierCode($carrierType, $carrierCode)
    {
        $carrierType = strstr($carrierType, "shqshared_") ? str_replace('shqshared_', '', $carrierType) : $carrierType;
        if(array_key_exists($carrierType, $this->magentoCarrierCodes)) {
            return $this->magentoCarrierCodes[$carrierType];
        }
        return $carrierCode;
    }

    public function refreshConfig()
    {
        if(Mage::helper('shipperhq_shipper')->getQuoteStorage()->getConfigUpdated()) {
            Mage::app()->getStore()->resetConfig();
            Mage::helper('shipperhq_shipper')->getQuoteStorage()->setConfigUpdated(false);
        }
    }

    public function isCityEnabled()
    {
        if($this->isModuleEnabled('Shipperhq_Lookup') && Mage::helper('shipperhq_lookup')->isPostcodeAutocompleteEnabled()) {
            return true;
        }
        $cityRequired = $this->getGlobalSetting('cityRequired');
        if(!$cityRequired) {
            $cityRequired = Mage::getStoreConfig('carriers/shipper/CARRIER_CITY_REQUIRED_DEFAULT');
        }
        return $cityRequired;
    }

    public function storeDimComments() {
        $storeDimComments = $this->getGlobalSetting('storeDimComments');
        if(!$storeDimComments) {
            $storeDimComments = Mage::getStoreConfig('carriers/shipper/STORE_DIM_COMMENTS');
        }
        return $storeDimComments;
    }

    public function getAlwaysShowSingleCarrierTitle() {
        $result = false;
        if (Mage::getStoreConfig('carriers/shipper/ALWAYS_SHOW_SINGLE_CARRIER_TITLE')) {
            $result = Mage::getStoreConfig('carriers/shipper/ALWAYS_SHOW_SINGLE_CARRIER_TITLE');
        }
        return $result;
    }

    public function isCityRequired()
    {
        return $this->isCityEnabled();
    }

    public function isAccessorialsEnabled()
    {
        return Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight');
    }

    public function getMethodTitle($methodTitle, $methodDescription, $includeContainer)
    {
        $title = $methodTitle;
        if($includeContainer) {
            $truncatedTitle = str_replace($methodDescription, '', $methodTitle);
            $title = '<span class="method-title">'.$truncatedTitle.'</span> <span class="method-extra">'.$methodDescription.'</span>';
        }
        return $title;
    }

    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title', $this->getQuote()->getStoreId())) {
            return $this->__($name);
        }

        return $carrierCode;
    }

    /**
     * Returns price formatted with OSC design elements
     *
     * @param $price
     */
    public function getOscFormattedPrice($price)
    {
        $title = '<strong><span class="price">'.$price.'</span></strong>';

        return $title;
    }

    public function getOscFormattedTooltip($tooltip)
    {
        $formattedTooltip = '<span style="float:right;" class="helpcursor" title="'.$tooltip.'">
                                <img src="'.Mage::getDesign()->getSkinUrl("images/shipperhq/tooltip.jpg").'">
                             </span>';

        return $formattedTooltip;
    }

    /**
     *
     * @return array
     */
    public function getProductAttributes()
    {
        if(is_null($this->_prodAttributes)) {
            /** @var $eavConfig Mage_Eav_Model_Config */
            $eavConfig = Mage::getSingleton('eav/config');

            $this->_prodAttributes = $eavConfig->getEntityAttributeCodes(Mage_Catalog_Model_Product::ENTITY);
        }

        return $this->_prodAttributes;
    }

    public function getProductsWithAttributeValue($attribute_code, $value, $storeId = null, $isSelect = false, $returnCount = true)
    {
        if($isSelect) {
            $value = $this->_getOptionId($this->getAttribute($attribute_code, $storeId), $value);
        }
        $collection = Mage::getModel('catalog/product')->setStoreId($storeId)->getCollection();
        if(!is_null($storeId) && $storeId != '') {
            $collection->addStoreFilter((int)$storeId);
        }

        $collection->addAttributeToSelect($attribute_code);
        $collection->addFieldToFilter(array(
            array('attribute'=>$attribute_code,'finset'=>$value),
        ));

        if($returnCount) {
            return count($collection);
        }
        return $collection;
    }

    public function getIsAttributeValueUsed($attribute_code, $value, $isSelect = false)
    {
        $attributeModel = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', $attribute_code);

        $select = Mage::getSingleton('core/resource')
            ->getConnection('default_read')->select()
            ->from($attributeModel->getBackend()->getTable(), 'value')
            ->where('attribute_id=?', $attributeModel->getId())
            ->where('value!=?','')
            ->distinct();

        $usedAttributeValues = Mage::getSingleton('core/resource')
            ->getConnection('default_read')
            ->fetchCol($select);
        if($isSelect) {
            //account for multiselect values
            $separated = array();
            foreach($usedAttributeValues as $key => $aValue) {
                if(strstr($aValue, ',')) {
                    $values = explode(',', $aValue);
                    $separated = array_merge($separated,$values);
                    unset($usedAttributeValues[$key]);
                }
            }
            $usedAttributeValues = array_merge($usedAttributeValues, $separated);

        }
        return in_array($value, $usedAttributeValues);
    }

    public function extractAddressIdAndCarriergroupId(&$addressId, &$carrierGroupId)
    {
        if(strstr($carrierGroupId, 'ma')) {
            $addressId = str_replace('ma', '', $carrierGroupId);
            $carrierGroupId = '';
            if(strstr($addressId, 'ZZ')) {
                $idArray = explode('ZZ', $addressId);
                $addressId = $idArray[0];
                $carrierGroupId = $idArray[1];
            }
        }
    }

    public function isOscDropdowns()
    {
        if($this->isModuleEnabled('Idev_OneStepCheckout')
            && Mage::getStoreConfig('onestepcheckout/general/condense_shipping_methods')) {
            return true;
        }
        return false;
    }

    public function useDefaultCarrierCodes()
    {
        $result = false;
        if(Mage::getStoreConfig('carriers/shipper/CARRIER_STRIP_CODE')) {
            $result = true;
        }
        return $result;
    }

    public function useInlineCalendar()
    {
        $result = false;
        if(Mage::getStoreConfig('carriers/shipper/CALENDAR_DISPLAY') == "Inline") {
            $result = true;
        }
        return $result;
    }

    public function isConfirmOrderRequired($carrierType)
    {
        return $carrierType == 'gso';
    }

    public function getCarriergroupShippingHtml($encodedDetails, $order = null)
    {
        $displayValues = array('destination_type', 'customer_carrier', 'customer_carrier_ph', 'customer_carrier_account');

        if($this->isModuleEnabled('Shipperhq_Freight')) {
            $options = Mage::helper('shipperhq_freight')->getAllNamedOptions();
        }
        else {
            $options = array();
        }
        //defaults
        $pickupDate = !is_null($order) ? $order->getPickupDate() : false;
        $timeSlot =  !is_null($order) ? $order->getTimeslot() : false;
        $pickupLocation = !is_null($order) ? $order->getPickupLocation() : false;
        $dispatchDate = !is_null($order) ? $order->getDispatchDate() : false;
        $deliveryDate = !is_null($order) ? $order->getDeliveryDate() : false;

        $cginfo =  $this->decodeShippingDetails($encodedDetails);
        $carriergroupText = '';
        foreach ($cginfo as $cgrp) {
            if (is_array($cgrp)) {
                //SHQ16-2023 reset to be values set on order
                $displayPickupDate = $pickupDate;
                $displayTimeSlot = $timeSlot;
                $displayPickupLocation = $pickupLocation;
                $displayDispatchDate = $dispatchDate;
                $displayDeliveryDate = $deliveryDate;

                $carriergroupText .= $cgrp['name'];
                $carriergroupText .= '<strong>: ' . $cgrp['carrierTitle'];
                $carriergroupText .= ' - ' . $cgrp['methodTitle'];
                $price =  !is_null($order) ? $order->formatPrice($cgrp['price']) : $this->getQuote()->getStore()->formatPrice($cgrp['price']);
                $carriergroupText .= ' ' . $price  . ' </strong>';
                if ((array_key_exists('carrierName', $cgrp) && $cgrp['carrierName'] != '')) {
                    $carriergroupText .= '<br /> Carrier: ';
                    $carriergroupText .= '' . strtoupper($cgrp['carrierName']);
                }

                if ((array_key_exists('pickup_date', $cgrp) && $cgrp['pickup_date'] != '')) {
                    $displayPickupDate = $cgrp['pickup_date'];
                }
                if ($displayPickupDate) {
                    $carriergroupText .= '<br /> Pickup : ';
                    if (array_key_exists('location_name', $cgrp)) {
                        $displayPickupLocation = $cgrp['location_name'];
                    }
                    if ($displayPickupLocation) {
                        $carriergroupText .= '' . $displayPickupLocation;
                    }
                    $carriergroupText .= ' ' . $displayPickupDate;
                    if (array_key_exists('pickup_slot', $cgrp)) {
                        $displayTimeSlot = $cgrp['pickup_slot'];
                        $displayTimeSlot = str_replace('_', ' - ', $cgrp['pickup_slot']);
                    }
                    if ($displayTimeSlot) {
                        $carriergroupText .= ' ' . $displayTimeSlot . ' ';
                    }
                }
                if (array_key_exists('dispatch_date', $cgrp)) {
                    $displayDispatchDate = $cgrp['dispatch_date'];
                }
                if ($displayDispatchDate) {
                    $carriergroupText .= '<br />' . Mage::helper('shipperhq_shipper')->__('Dispatch Date') . ' : ' . $displayDispatchDate;
                }

                if (array_key_exists('delivery_date', $cgrp)) {
                    $displayDeliveryDate = $cgrp['delivery_date'];
                }
                if ($displayDeliveryDate) {
                    $carriergroupText .= '<br />' . Mage::helper('shipperhq_shipper')->__('Delivery Date') . ' : ' . $displayDeliveryDate;
                    if (array_key_exists('del_slot', $cgrp)) {
                        $displayTimeSlot = $cgrp['del_slot'];
                        $displayTimeSlot = str_replace('_', ' - ', $cgrp['del_slot']);
                    }
                    if ($displayTimeSlot) {
                        $carriergroupText .= ' ' . $displayTimeSlot . ' ';
                    }
                }
                foreach ($options as $code => $name) {
                    $value = false;
                    if (array_key_exists($code, $cgrp) && $cgrp[$code] != '') {
                        $value = $cgrp[$code];
                    }
                    //SHQ16-1605 limit to display to selected carrier group only
//                        elseif($order->getData($code)) {
//                            $value = $order->getData($code);
//                        }
                    if ($value) {
                        $carriergroupText .= '<br />' . $name;
                        if (in_array($code, $displayValues)) {
                            $carriergroupText .= ': ' . $value;
                        }
                    }
                }
                if (array_key_exists('freightQuoteId', $cgrp) && $cgrp['freightQuoteId'] != '') {
                    $carriergroupText .= ' Quote Id: ' . $cgrp['freightQuoteId'];
                }
                $carriergroupText .= '<br/><br/>';
            }
        }
        return $carriergroupText;
    }

    public function setShippingOnItems($shippingDetails, $shippingAddress)
    {
        $itemsGrouped = $this->getItemsGroupedByCarrierGroup($shippingAddress->getAllItems());

        if(!count($shippingDetails)) {//SHQ16-1932
            foreach($itemsGrouped as $cgItemsGrouped) {
                foreach ($cgItemsGrouped as $item) {
                    $item->setCarriergroupShipping(null);
                }
            }

            return null;
        }

        foreach($shippingDetails as $carrierGroupDetail)
        {
            if(is_array($carrierGroupDetail) && array_key_exists('carrierTitle', $carrierGroupDetail)) {
                $carrierGroupId = $carrierGroupDetail['carrierGroupId'];
                $shippingText = $carrierGroupDetail['carrierTitle'] .' - ' .$carrierGroupDetail['methodTitle'];
                if(array_key_exists('delivery_date', $carrierGroupDetail) && $carrierGroupDetail['delivery_date'] != '') {
                    $shippingText .= ' Delivery: ' .$carrierGroupDetail['delivery_date'];
                }
                if(array_key_exists('dispatch_date', $carrierGroupDetail) && $carrierGroupDetail['dispatch_date'] != '') {
                    $shippingText .= ' Dispatch: ' .$carrierGroupDetail['dispatch_date'];
                }
                // if(array_key_exists('time_slot'))
                if(array_key_exists($carrierGroupId, $itemsGrouped)) {
                    foreach($itemsGrouped[$carrierGroupId] as $item) {
                        $item->setCarriergroupShipping($shippingText);
                    }
                }


            }

        }
    }

    /**
     * Format items based on carrier group
     *
     * @return array
     */
    public function getItemsGroupedByCarrierGroup($cartItems)
    {
        $groupedItems = array();
        foreach($cartItems as $item)
        {
            if(array_key_exists($item->getCarriergroupId(), $groupedItems)) {
                $groupedItems[$item->getCarriergroupId()][] = $item;
            }
            else {
                $groupedItems[$item->getCarriergroupId()]= array($item);
            }
        }

        return $groupedItems;
    }

    public function cleanDownRatesCollection($address, $carrierCode, $carriergroupId)
    {
        $currentRates = $address->getGroupedAllShippingRates();
        foreach($currentRates as $code => $rates)
        {
            if($code == $carrierCode){
                foreach($rates as $rate) {
                    if($carriergroupId == '' || $rate->getCarriergroupId() == $carriergroupId) {
                        $rate->isDeleted(true);
                    }
                }
            }

        }
    }

    public function isMobile()
    {
        $isMobile = Zend_Http_UserAgent_Mobile::match(
            Mage::helper('core/http')->getHttpUserAgent(),
            $_SERVER
        );
        return $isMobile;
    }

    public function getGoogleApiUrl()
    {

        if (!Mage::getStoreConfig('carriers/shipper/active')) {
            return '';
        }
        $locationDisplayOptions = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getPickupDisplayConfig();
        $hasApiKey = false;

        if(!is_null($locationDisplayOptions) && array_key_exists('google_api_key', $locationDisplayOptions) && $locationDisplayOptions['google_api_key'] != '') {
            $hasApiKey =  trim($locationDisplayOptions['google_api_key']);
        }

        if ($hasApiKey) {
            $url = "//maps.googleapis.com/maps/api/js?key=" .$hasApiKey;
        }
        else {
            $url = "//maps.google.com/maps/api/js";
        }
        $text = '<script type="text/javascript" src=' . $url . '></script>';

        return $text;
    }

    protected function _getOptionId($attribute, $value)
    {
        //get the source
        $source = $attribute->getSource();
        //get the id
        $id = $source->getOptionId($value);
        return $id;
    }

    protected function getAttribute($attribute_code, $store = null) {
        $attribute = Mage::getResourceModel('catalog/product')
            ->getAttribute($attribute_code);

        if(is_null($store) || $store == '') {
            $store = Mage_Core_Model_App::ADMIN_STORE_ID;
        }
        $attribute->setStoreId($store);

        return $attribute;
    }

    protected function _dynamicCarrierConfig($carrierCode, $carrierType, $carrierTitle, $sortOrder = false)
    {
        $modelPath = 'carriers/'.$carrierCode.'/model';
        if(!Mage::getStoreConfig($modelPath)) {
            if($this->isModuleEnabled('Shipperhq_Pickup') &&
                Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($carrierType)) {
                $model =  'shipperhq_pickup/carrier_storepickup';
            } else {
                $model = 'shipperhq_shipper/carrier_shipper';
            }
            $this->saveConfig($modelPath, $model);
            $this->saveConfig('carriers/'.$carrierCode.'/active', 0);
        }
        $this->saveCarrierTitle($carrierCode, $carrierTitle);
        if(!is_null($sortOrder)) {
            $this->saveConfig('carriers/'.$carrierCode.'/sort_order', $sortOrder);
        }


    }

    /**
     * Returns url to use - live if present, otherwise dev
     * @return array
     */
    protected function _getGatewayUrl()
    {
        $live = $this->_cleanUpUrl(Mage::getStoreConfig('carriers/shipper/live_url'));

        $test = $this->_cleanUpUrl(Mage::getStoreConfig('carriers/shipper/url'));
        return Mage::getStoreConfig('carriers/shipper/sandbox_mode') ? $test : $live;
    }

    protected function _cleanUpUrl($urlStart)
    {
        $url = trim($urlStart);
        $lastChar = substr("abcdef", -1);
        if($lastChar != '/') {
            $url .= '/';
        }
        return $url;
    }

    public function getMagentoUpsMethodCode($shqUpsMethodCode)
    {
        $shqCodeToUpsCode = array(
            '1DA' => '01',
            '2DA' => '02',
            'GND' => '03',
            'XPR' => '07',
            'XPD' => '08',
            'STD' => '11',
            '3DS' => '12',
            '1DP' => '13',
            '1DM' => '14',
            'XDM' => '54',
            '2DM' => '59'
        );

        if(!isset($shqCodeToUpsCode[$shqUpsMethodCode])) {
            return false;
        } else {
            return $shqCodeToUpsCode[$shqUpsMethodCode];
        }
    }

    public function getStoreId() {
        $store_id = 0;

        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $store_id = (int)Mage::getModel('core/store')->load($code)->getId();
        }
        elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
        {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = (int)Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }

        return $store_id;
    }

}
