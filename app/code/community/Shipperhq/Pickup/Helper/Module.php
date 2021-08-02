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
class Shipperhq_Pickup_Helper_Module extends Mage_Core_Helper_Abstract
{
    // These are automatically set back up via Shipperhq_Shipper_Helper_Data::_dynamicCarrierConfig
//    /**
//     * @return bool
//     */
//    public function moduleEnableAfter() {
//        $config = Mage::getConfig()->saveConfig('carriers/shqpickup/model', 'shipperhq_pickup/carrier_storepickup');
//        return true;
//    }

    /**
     * @return bool
     */
    public function moduleDisableAfter() {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $tableName = $resource->getTableName('core_config_data');

        $query = <<<MYSQL
SELECT
    `path`
FROM `$tableName`
WHERE
  `path` LIKE 'carriers/%/model'
  AND `value` = 'shipperhq_pickup/carrier_storepickup'
MYSQL;
        $results = $readConnection->fetchAll($query);

        foreach ($results as $result) {
            $config = Mage::getConfig()->deleteConfig($result['path']);
        }
        return true;
    }
}
