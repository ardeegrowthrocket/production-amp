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

class Shipperhq_Pickup_Block_Checkout_Multishipping_Addresses extends Mage_Checkout_Block_Multishipping_Addresses
{

    protected $_pickupEnabledCarriergroups;

    protected $_dummyPickupAddress;
    /**
     * Retrieve HTML for addresses dropdown
     *
     * @param  $item
     * @return string
     */
    public function getAddressesHtmlSelect($item, $index)
    {
        if(!Mage::helper('shipperhq_pickup')->preselectPickupEnabled()) {
            return parent::getAddressesHtmlSelect($item, $index);
        }
        $isPickup = $this->isPickupEnabledForItem($item);
        $options = $this->getAddressOptions();
        $defaultValue = $item->getCustomerAddressId();
        $pickupAddressId = Mage::helper('shipperhq_pickup')->getPreselectPickupAddressId();
        if($isPickup) {
            $defaultValue = $pickupAddressId;
            $options[] = array('value' =>$pickupAddressId, 'label' =>$isPickup);
        }

        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName('ship['.$index.']['.$item->getQuoteItemId().'][address]')
            ->setId('ship_'.$index.'_'.$item->getQuoteItemId().'_address')
            ->setValue($defaultValue)
            ->setOptions($options);

        return $select->getHtml();
    }

    protected function isPickupEnabledForItem($item)
    {
        $pickupCGs = $this->_getPickupEnabledCarrierGroups();
        if(array_key_exists($item->getCarriergroupId(), $pickupCGs )){
            return $pickupCGs[$item->getCarriergroupId()];
        }
        return false;
    }

    protected function _getPickupEnabledCarrierGroups()
    {
        if(is_null($this->_pickupEnabledCarriers)) {
            $shippingRates = array();
            foreach( Mage::helper('shipperhq_shipper')->getQuote()->getAllShippingAddresses() as $shipAddress) {
                $thisRates = $shipAddress->getGroupedAllShippingRates();
                $shippingRates = array_merge($shippingRates, $thisRates);
            }
            $pickupType = Shipperhq_Pickup_Model_Carrier_Storepickup::PICKUP_CARRIER_TYPE;
            $carriergroups = array();
            foreach($shippingRates as $carrier => $rates)
            {
               foreach($rates as $rate) {
                    if($rate->getCarrierType() == $pickupType) {
                        if(!in_array($rate->getCarriergroupId(), $carriergroups)) {
                            $carriergroups[$rate->getCarriergroupId()] = $rate->getCarrierTitle();
                        }
                    }
               }
            }
            $this->_pickupEnabledCarriergroups = $carriergroups;
        }
        return $this->_pickupEnabledCarriergroups;

    }

}

