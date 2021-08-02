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
class Shipperhq_Freight_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected static $_debug;
    protected $_quoteStorage;
    protected $_genericAccessorials;
    protected $_accessorials;

    protected $_allOptions = array('liftgate_required', 'inside_delivery', 'destination_type', 'notify_required',
                                    'customer_carrier', 'customer_carrier_ph', 'customer_carrier_account',  'limited_delivery');
    protected $_allNamedOptions = array(
        'liftgate_required' => 'Liftgate Required',
        'inside_delivery' => 'Inside Delivery',
        'destination_type' => 'Destination Type',
        'notify_required' => 'Notify Required',
        'customer_carrier' => 'Customer Carrier',
        'customer_carrier_ph' => 'Customer Carrier Phone',
        'customer_carrier_account' => 'Customer Carrier Account Number',
        'limited_delivery' => 'Limited Access for Delivery');

    protected static $CUSTOMERACCOUNTCARRIERTYPE = 'customerAccount';

    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isDebug()
    {
        if (self::$_debug == NULL) {
            self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Freight');
        }
        return self::$_debug;
    }

    public function getAccessorialParams($quote)
    {
        $params = array();
        $shippingAddress = $quote->getShippingAddress();
        $params['country_id'] = $shippingAddress->getCountryId();
        $params['region_id'] = $shippingAddress->getRegionId();
        $params['city']= $shippingAddress->getCity();
        $params['postcode'] = $shippingAddress->getPostcode();

        $items = array();
        foreach($quote->getAllItems() as $item) {
            $items[$item->getSku()] = $item->getQty();
        }
        $params['items'] = $items;
        return $params;
    }

    public function isDestinationTypeOptionEnabled($carriergroupId = null, $carrierCode = null)
    {
        //checks if array key exists and options available
        if($options = $this->getDestinationTypeOptions($carriergroupId, $carrierCode)) {
            return true;
        }
        return false;
    }

    public function getDestinationTypeHtmlSelect($defValue = null, $carrierGroupId = null, $carrierCode = null)
    {
        $layout = Mage::getSingleton('core/layout');
        $block = $layout->createBlock('shipperhq_freight/accessorials');
        $html = $block->getDestinationTypeHtmlSelect($defValue, $carrierCode, $carrierGroupId);
        return $html;
    }

    public function getDestinationTypeOptions($carrierGroupId = null, $carrierCode = null)
    {
        $options = false;
        $freightAcc = $this->getFreightAccessorials($carrierGroupId, $carrierCode);
        if($freightAcc && array_key_exists('destination_type',$freightAcc)
          && array_key_exists('values',$freightAcc['destination_type'])
          && !empty($freightAcc['destination_type']['values'])) {
            $options = $freightAcc['destination_type']['values'];
        }
        return $options;
    }

    public function getDestinationType($carrierGroupId = null, $carrierCode = null)
    {
        return strtolower($this->getOptionValue('destination_type', $carrierGroupId, $carrierCode));

    }

    public function isLiftgateEnabled($carrierGroupId = null, $carrierCode = null)
    {
        return $this->isAccessorialEnabled('liftgate_required', $carrierGroupId, $carrierCode);
    }

    public function getLiftgateRequired($carrierGroupId = null, $carrierCode = null)
    {
        return $this->getOptionValue('liftgate_required', $carrierGroupId, $carrierCode);
    }

    public function isNotifyOptionEnabled($carrierGroupId = null, $carrierCode = null)
    {
        return $this->isAccessorialEnabled('notify_required', $carrierGroupId, $carrierCode);
    }

    public function getNotifyRequired($carrierGroupId = null, $carrierCode = null)
    {
        return $this->getOptionValue('notify_required', $carrierGroupId, $carrierCode);
    }

    public function isInsideDeliveryEnabled($carrierGroupId = null, $carrierCode = null)
    {
        return $this->isAccessorialEnabled('inside_delivery', $carrierGroupId, $carrierCode);
    }

    public function getInsideDelivery($carrierGroupId = null, $carrierCode = null)
    {
        return $this->getOptionValue('inside_delivery', $carrierGroupId, $carrierCode);
    }

    public function isCustomerAccountCarrier($carrierGroupId = null, $carrierCode = null, $carrierType = null)
    {
        return $carrierType == self::$CUSTOMERACCOUNTCARRIERTYPE;
    }

    public function isLimitedDeliveryEnabled($carrierGroupId = null, $carrierCode = null)
    {
        return $this->isAccessorialEnabled('limited_delivery', $carrierGroupId, $carrierCode);
    }

    public function getLimitedDelivery($carrierGroupId = null, $carrierCode = null)
    {
        return $this->getOptionValue('limited_delivery', $carrierGroupId, $carrierCode);
    }

    public function isAccessorialEnabled($accessorialCode, $carrierGroupId = null, $carrierCode = null)
    {
        $accessorials = $this->getFreightAccessorials($carrierGroupId, $carrierCode);
        $result = $accessorials && array_key_exists($accessorialCode, $accessorials);
        return $result;

    }

    public function getOptionValue($accessorialCode, $carrierGroupId = null, $carrierCode = null)
    {

        //check the value returned in selectedValue on saved accessorials (this is saved from checkout selections)
        $freightAcc = $this->getFreightAccessorials($carrierGroupId, $carrierCode);
        if($freightAcc && array_key_exists($accessorialCode,$freightAcc)
            && array_key_exists('selectedValue',$freightAcc[$accessorialCode])
            && $freightAcc[$accessorialCode]['selectedValue'] != ''
            && !is_null($freightAcc[$accessorialCode]['selectedValue'])) {
            return $freightAcc[$accessorialCode]['selectedValue'];
        }
        //shipping address is set when values selected at checkout
        $currentVal = Mage::helper('shipperhq_shipper')->getQuote()->getShippingAddress()->getData($accessorialCode);
        if ($currentVal != '' && $currentVal != null) {
            return $currentVal;
        }
        if($accessorialCode == 'destination_type' && Mage::registry('Shipperhq_Destination_Type') != '') {
            return Mage::registry('Shipperhq_Destination_Type');
        }

        //check selected Freight Carrier options on session - these are saved from cart selections
        $selectedFreightOptions = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getSelectedFreightCarrier();
        //destination type is case sensitive in SHQ
        if (isset($selectedFreightOptions[$accessorialCode]) && $selectedFreightOptions[$accessorialCode] !== '') {
            if ($accessorialCode === 'destination_type' && $selectedFreightOptions[$accessorialCode] === 'false') {
                //ignore
            }
            else {
                return strtolower($selectedFreightOptions[$accessorialCode]);
            }
        }

        //check default option values
        if($freightAcc && array_key_exists($accessorialCode,$freightAcc)
            && array_key_exists('defaultOptionValue',$freightAcc[$accessorialCode])
            && $freightAcc[$accessorialCode]['defaultOptionValue'] != ''
            && !is_null($freightAcc[$accessorialCode]['defaultOptionValue'])) {
            return $freightAcc[$accessorialCode]['defaultOptionValue'];
        }
        return false;
    }

    public function addSelectedFreightOptionsToRequest(&$request)
    {
        if ($selectedFreightOptions = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getSelectedFreightCarrier()){
            if(array_key_exists('carrier_code', $selectedFreightOptions)) {

                $storedAccessorials = $this->getFreightAccessorials(
                    $selectedFreightOptions['carriergroup_id'],$selectedFreightOptions['carrier_code'] );
                if($storedAccessorials && array_key_exists('carrier_id', $storedAccessorials)) {
                    if($selectedFreightOptions['carrier_code'] != 'multicarrier') //SHQ16-1605 don't specify a carrier when its merged rates
                    {
                        $request->setCarrierId($storedAccessorials['carrier_id']);
                    }
                }
                $request->setCarriergroupId($selectedFreightOptions['carriergroup_id']);
            }
        }
    }

    public function getAllPossibleOptions()
    {
        return $this->_allOptions;
    }

    public function getAllNamedOptions()
    {
        return $this->_allNamedOptions;
    }

    public function isFreightEnabledMergedRate($carrierCode)
    {
        $accessorials = $this->getFreightAccessorials('', $carrierCode);
        return count($accessorials) > 0;
    }

    public function getFreightAccessorials($carrierGroupId = null, $carrierCode = null)
    {
        $storedAcc = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getFreightAccessorials();
        $acc = false;
        //merge the stored accessorials into one as we are displaying globally in the cart
        if(is_null($carrierGroupId) && is_null($carrierCode)) {
            $acc = $this->getGenericAccessorials($storedAcc);

        }
        else {
            if($carrierGroupId == '') $carrierGroupId = 0;
                if(is_array($storedAcc) && array_key_exists($carrierGroupId, $storedAcc)
                && array_key_exists($carrierCode, $storedAcc[$carrierGroupId])) {
                $carrierAcc = $storedAcc[$carrierGroupId][$carrierCode];
                $acc = array();
                foreach ($carrierAcc as $accessorial) {
                    $accessorialArray = (array)$accessorial;
                    if(array_key_exists('code', $accessorialArray))  {
                        $acc[$accessorialArray['code']] = $accessorialArray;
                    }
                }
                if(count($acc) > 0) {
                    $acc['carrier_id'] = array_key_exists('carrier_id', $carrierAcc) ? $carrierAcc['carrier_id'] : false;
                }
            }
        }
        return $acc;

    }

    public function parseFreightDetails($response, $isCheckout = false)
    {
        $freightService = Mage::getSingleton('shipperhq_freight/service_accessorials');
        $result = $freightService->processResponse($response);
        $this->getQuoteStorage()->setFreightAccessorialsRequest(null);
        if($result) {
            $freightService->setCachedAccessorials(false, $result, $isCheckout);
        }
    }

    protected function getGenericAccessorials($rawAcc)
    {
        if(!$this->_genericAccessorials) {
           $acc = array();
           if(is_array($rawAcc)) {
               foreach ($rawAcc as $carrierGroup) {
                    foreach ($carrierGroup as $carrierAcc) {
                        foreach($carrierAcc as $accessorialConfig) {
                            if(array_key_exists($accessorialConfig['code'], $acc)) {
                                continue;
                            }
                            $acc[$accessorialConfig['code']] = $accessorialConfig;
                        }
                    }
               }
           }

            $this->_genericAccessorials = $acc;
        }
        return $this->_genericAccessorials;
    }

    protected function getQuoteStorage()
    {
        if(!$this->_quoteStorage) {
            $this->_quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage();
        }
        return $this->_quoteStorage;
    }
}
