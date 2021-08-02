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
 * Class Shipperhq_Validation_Model_Validator_ValidationMapper
 *
 * This class converts the Magento objects into a format that
 * is usable by the ShipperHQ webservice
 */

include_once 'ShipperHQ/User/Credentials.php';
include_once 'ShipperHQ/User/SiteDetails.php';
include_once 'ShipperHQ/WS/Request/Validation/Request.php';
include_once 'ShipperHQ/Validation/Address.php';


class Shipperhq_Validation_Model_Validator_ValidationMapper {


    protected static $ecommerceType = 'magento';

    protected static $_useDefault = 'Use Default';

    function __construct() {

    }
    /**
     * Set up values for ShipperHQ Validation Request
     *
     * @param $magentoRequest
     * @return string
     */
    public static function getTranslation($address)
    {

        $shipperHQRequest = new \ShipperHQ\WS\Request\Validation\Request(
            self::getAddressDetails($address)
        );

        $shipperHQRequest->setSiteDetails(self::getSiteDetails());
        $shipperHQRequest->setCredentials(self::getCredentials());

        return $shipperHQRequest;
    }


    /**
     * Format address
     *
     * @param $address
     * @return array
     */
    public static function getAddressDetails($unformattedAddress)
    {
        if(is_object($unformattedAddress)) {
            $unformattedAddress = self::getAddresssArray($unformattedAddress);
        }
        $regionCode=$unformattedAddress['region_id'];
        if (is_numeric($regionCode)) {
            $regionCode = Mage::getModel('directory/region')->load($regionCode)->getCode();
        }

        $postCode = $unformattedAddress['postcode'];

        $destCountry = $unformattedAddress['country_id'];

        //for UPS, puero rico state for US will assume as puerto rico country
        if ($destCountry == Mage_Usa_Model_Shipping_Carrier_Abstract::USA_COUNTRY_ID
            && ($postCode =='00912' || $regionCode==Mage_Usa_Model_Shipping_Carrier_Abstract::PUERTORICO_COUNTRY_ID)
        ) {
            $destCountry = Mage_Usa_Model_Shipping_Carrier_Abstract::PUERTORICO_COUNTRY_ID;
        }

        // For UPS, Guam state of the USA will be represented by Guam country
        if ($destCountry == Mage_Usa_Model_Shipping_Carrier_Abstract::USA_COUNTRY_ID
            && $regionCode == Mage_Usa_Model_Shipping_Carrier_Abstract::GUAM_REGION_CODE) {
            $destCountry = Mage_Usa_Model_Shipping_Carrier_Abstract::GUAM_COUNTRY_ID;
        }

        $destCountry = Mage::getModel('directory/country')->load($destCountry)->getIso2Code();
        if(is_array($unformattedAddress['street'])) {
            $street1 = $unformattedAddress['street'][0];
            $street2 = $unformattedAddress['street'][1];
        }
        else {
            $street1 = $unformattedAddress['street'];
            $street2 = '';
        }

        $address = new \ShipperHQ\Validation\Address(
            $unformattedAddress['city'],
            $destCountry,
            $regionCode,
            $street1,
            $street2,
            $postCode
        );
        return $address;
    }

    /**
     * Return credentials for ShipperHQ login
     *
     * @return array
     */
    public static function getCredentials()
    {

        $credentials = new \ShipperHQ\User\Credentials(Mage::getStoreConfig('carriers/shipper/api_key'),
            Mage::getStoreConfig('carriers/shipper/password'));
        return $credentials;
    }


    /**
     * Return site specific information
     *
     * @return array
     */
    public static function getSiteDetails()
    {
        $edition = 'Community';
        if(method_exists('Mage', 'getEdition')) {
            $edition = Mage::getEdition();
        }
        elseif(Mage::helper('wsalogger')->isEnterpriseEdition()) {
            $edition = 'Enterprise';
        }
        $siteDetails = new \ShipperHQ\User\SiteDetails('Magento ' . $edition, Mage::getVersion(),
            Mage::getBaseUrl(), Mage::getStoreConfig('carriers/shipper/environment_scope'),
            (string)Mage::getConfig()->getNode('modules/Shipperhq_Shipper/extension_version'));

        return $siteDetails;
    }

    protected static function getAddresssArray($unformattedAddress)
    {
        $addressArray = array();
        $addressArray['city'] =  $unformattedAddress->getCity();
        $addressArray['country_id'] = $unformattedAddress->getCountryId();
        $addressArray['region_id'] = $unformattedAddress->getRegionCode();
        $addressArray['street'] = array($unformattedAddress->getStreet(1), $unformattedAddress->getStreet(2)) ;
        $addressArray['postcode'] = $unformattedAddress->getPostcode();

        return $addressArray;
    }
}
