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

class Shipperhq_Postorder_Model_Shipping_Carrier_Source_ShipStatus
{
    const SHIPPERHQ_SHIPSTATUS_PENDING = 0;
    const SHIPPERHQ_SHIPSTATUS_SHIPPED = 1;

    public function toOptionArray()
    {
        return array(
            '0' => Mage::helper('shipperhq_postorder')->__('Pending'),
            '1' => Mage::helper('shipperhq_postorder')->__('Shipped'),
        );
    }

    public static function getOption($option) {
        switch ($option) {
            case '0':
                return 'Pending';
                break;
            case '1':
                return 'Shipped';
                break;
            default:
                return 'Unknown';
                break;
        }
    }
}