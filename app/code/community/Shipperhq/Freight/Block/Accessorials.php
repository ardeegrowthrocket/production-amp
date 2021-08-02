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
class Shipperhq_Freight_Block_Accessorials
    extends Shipperhq_Frontend_Block_Checkout_AbstractBlock
{

    public function getDestinationTypeHtmlSelect($destinationType, $carrierCode, $carrierGroupId)
    {
        if (is_null($destinationType)) {
            $destinationType = Mage::helper('shipperhq_freight')->getDestinationType($carrierGroupId, $carrierCode);
        }
        $carrierCodeInsert = is_null($carrierCode) ? '': '_' .$carrierCode;
        $carrierGroupInsert = $carrierGroupId == '' || is_null($carrierGroupId)? '' : '_' . $carrierGroupId;
        $options = Mage::helper('shipperhq_freight')->getDestinationTypeOptions($carrierGroupId, $carrierCode);
        $html = $this->getLayout()->createBlock('core/html_select')
            ->setName('destination_type'.$carrierCodeInsert.$carrierGroupInsert)
            ->setTitle(Mage::helper('shipperhq_freight')->__('Address Type'))
            ->setId('destination_type'.$carrierCodeInsert.$carrierGroupInsert)
            ->setClass('required-entry accessorial')
            ->setValue(strtolower($destinationType))
            ->setOptions($options)
            ->getHtml();

        return $html;

    }

    public function oneStepCheckoutEnabled()
    {
        return Mage::helper('shipperhq_shipper')->isModuleEnabled('Idev_OneStepCheckout', 'onestepcheckout/general/rewrite_checkout_links');
    }

    public function oscShowDropDowns()
    {
        return Mage::getStoreConfig('onestepcheckout/general/condense_shipping_methods');
    }
}