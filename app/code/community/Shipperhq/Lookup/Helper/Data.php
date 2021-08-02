<?php
/**
 * Fontis Australia Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Chris Norton
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
/**
 * Data helper
 */
class Shipperhq_Lookup_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_POSTCODE_AUTOCOMPLETE_ENABLED = 'carriers/shipper/enable_lookup';

    const MAX_QUERY_LEN = 100;

    const MAX_AUTOCOMPLETE_RESULTS_DEFAULT = 20;

    protected $_queryText;

    /**
     * Gets the query text for city lookups in the postcode database.
     */
    public function getQueryText()
    {
        if (is_null($this->_queryText)) {
            if ($this->_getRequest()->getParam('billing')) {
                $tmp = $this->_getRequest()->getParam('billing');
                $this->_queryText = $tmp['city'];
            } elseif ($this->_getRequest()->getParam('shipping')) {
                $tmp = $this->_getRequest()->getParam('shipping');
                $this->_queryText = $tmp['city'];
            } elseif ($this->_getRequest()->getParam('estimate_city')) {
                $this->_queryText = $this->_getRequest()->getParam('estimate_city');
            } else {
                $this->_queryText = $this->_getRequest()->getParam('city');
            }
            $this->_queryText = trim($this->_queryText);
            if (Mage::helper('core/string')->strlen($this->_queryText) > self::MAX_QUERY_LEN) {
                $this->_queryText = Mage::helper('core/string')->substr($this->_queryText, 0, self::MAX_QUERY_LEN);
            }
        }
        return $this->_queryText;
    }

    public function getQueryCountry()
    {
        return $this->_getRequest()->getParam('country');
    }

    public function getCitySuggestUrl()
    {
        return $this->_getUrl('shipperhq_lookup/ajax/suggest', array('_secure'=>true));
    }

    /**
     * Checks whether postcode autocomplete is enabled.
     *
     * @return bool
     */
    public function isPostcodeAutocompleteEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_POSTCODE_AUTOCOMPLETE_ENABLED);
    }

    /**
     * @return int
     */
    public function getPostcodeAutocompleteMaxResults()
    {
        $max = Mage::getStoreConfig("carriers/shipper/max_results");
        if (!is_numeric($max)) {
            return self::MAX_AUTOCOMPLETE_RESULTS_DEFAULT;
        }
        $max = (int) $max;
        if ($max > 0) {
            return $max;
        } else {
            return self::MAX_AUTOCOMPLETE_RESULTS_DEFAULT;
        }
    }

    /**
     * @return array
     */
    public function getPostcodeAutocompleteResults()
    {
        $country = $this->getQueryCountry();

        $res = Mage::getSingleton('core/resource');
        /* @var $conn Varien_Db_Adapter_Pdo_Mysql */
        $conn = $res->getConnection('shipperhq_lookup_read');
        return $conn->fetchAll(
            'SELECT sub.*, dcr.region_id FROM ' . $res->getTableName('shipperhq_suburb_lookup') . ' AS sub
             INNER JOIN ' . $res->getTableName('directory_country_region') . ' AS dcr ON sub.region_code = dcr.code
             and dcr.country_id = "' .$country .'"
             WHERE city LIKE :city ORDER BY city, region_code, postcode
             LIMIT ' . $this->getPostcodeAutocompleteMaxResults(),
            array('city' => $this->getQueryText() . '%')
        );
    }
}
