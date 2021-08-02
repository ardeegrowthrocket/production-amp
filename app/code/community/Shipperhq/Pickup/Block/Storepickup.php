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
class Shipperhq_Pickup_Block_Storepickup
    extends Shipperhq_Frontend_Block_Checkout_AbstractBlock
{
    protected static $_locationOptions;
    protected static $_displayOptions;

    /**
     * Populates the location select dropdown
     * @return string
     */
    public function getStoreLocationsHtmlSelect($carrierCodeInsert, $carriergroupInsert)
    {
        $currentLocationId = $this->getAddress()->getPickupLocation();

        $className = 'location-select';

        $id = 'location-select' .$carrierCodeInsert .$carriergroupInsert;
        $name = 'location_id' .$carrierCodeInsert .$carriergroupInsert;
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($name)
            ->setId($id)
            ->setClass($className)
            ->setValue($currentLocationId)
            ->setOptions($this->_locationOptions);
        return $select->getHtml();

    }

    public function hasStoreLocations($carriergroupId, $carrierCode)
    {
        if(strstr($carriergroupId, 'ma')) {
            if(strstr($carriergroupId, 'ZZ')) {
                $parts = explode('ZZ', $carriergroupId);
                $carriergroupId = $parts[1];
            }
            else {
                $carriergroupId = '';
            }
        }
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')) {
            return false;
        }

        $this->setStoreLocations($this->getLocationOptions($carriergroupId, $carrierCode));
        if ($this->_locationOptions && count($this->_locationOptions) > 0) {
            return true;
        }

        return false;

    }

    public function setStoreLocations($locations)
    {
        $this->_locationOptions = $locations;
    }

    public function oneStepCheckoutEnabled()
    {
        return Mage::helper('shipperhq_shipper')->isModuleEnabled('Idev_OneStepCheckout', 'onestepcheckout/general/rewrite_checkout_links');
    }

    public function getPickupSlotHtmlSelect($carrierCodeInsert, $carriergroupInsert)
    {
        $className = 'pickup-slot-select';
//        if(Mage::getStoreConfig('carriers/shipper/new_styling')) {
//            $className .= ' shq-input';
//        }

        $id = 'pickup_slot_select' .$carrierCodeInsert .$carriergroupInsert;
        $name = 'pickup_slot' .$carrierCodeInsert . $carriergroupInsert;
        $selectedTimeSlot =  $this->getAddress()->getTimeSlot();
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($name)
            ->setId($id)
            ->setValue($selectedTimeSlot)
            ->setClass($className);

        return $select->getHtml();
    }

    public function getLocationOptions($carriergroupId, $carrierCode)
    {
        if($carriergroupId == '') {
            $carriergroupId = 0;
        }
        $options = array();

        $closestLocations = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getClosestLocations();
        $globals = $this->helper('shipperhq_shipper')->getGlobalSettings();;

        if(!is_null($globals) && array_key_exists('distanceUnit', $globals)) {
            $longMeasurement = $globals['distanceUnit'];
        }
        else {
            $longMeasurement = false;
        }

        if(!is_null($closestLocations) && array_key_exists($carriergroupId, $closestLocations) &&
            array_key_exists($carrierCode, $closestLocations[$carriergroupId])) {
            foreach ($closestLocations[$carriergroupId][$carrierCode] as $location) {

                if(array_key_exists('pickupFullName', $location)) { //SHQ16-1342
                    $label =  str_replace(", null", "", $location['pickupFullName']);
                } else {
                    $label = $location['pickupName'];
                }

                if($longMeasurement && $location['distance'] != '6371') {
                    $label .=' ('. Mage::helper('shipperhq_shipper')->__('Distance') .' '.
                        number_format($location['distance']).' '.$longMeasurement.')';
                }
                $options[] = array(
                    'value' => $location['pickupId'],
                    'label' => $label,
                );
            }
            $this->setStoreLocations($options);
            return $options;
        }

        return false;
    }

    public function getPickupLocation()
    {
        return $this->getAddress()->getPickupLocation();
    }

    public function getShowMap()
    {
        $pickupDisplay = $this->getDisplayOptions();
        if(array_key_exists('show_map', $pickupDisplay)) {
            return $pickupDisplay['show_map'];
        }
        return false;
    }

    public function getShowAddress()
    {
        $pickupDisplay = $this->getDisplayOptions();
        if(array_key_exists('show_address', $pickupDisplay)) {
            return $pickupDisplay['show_address'];
        }
        return false;
    }

    public function hideZipcodeSearch()
    {
        $options = $this->getDisplayOptions();
        if(array_key_exists('show_zipcodesearch', $options)) {
            if($options['show_zipcodesearch'] == 1){
                return false;
            }
        }
        return true;

    }

    public function getDisplayOptions()
    {
        if(!$this->_displayOptions) {
            $this->setDisplayOptions(Mage::helper('shipperhq_shipper')->getQuoteStorage()->getPickupDisplayConfig());
        }
        return $this->_displayOptions;
    }

    public function setDisplayOptions($options)
    {
        $this->_displayOptions = $options;

    }

    public function getCarriergroupInsert($carriergroupId) {
        if (is_empty($carriergroupId)) {
            return '';
        } else {
            return '_'.$carriergroupId;
        }
    }

}