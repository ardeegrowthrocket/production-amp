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

class Shipperhq_Postorder_Block_Adminhtml_Sales_Items_Carriergroup extends Mage_Adminhtml_Block_Sales_Order_View_Items
{
    public function getSimpleCarriergroupText()
    {
        $items = $this->getItemsCollection();
        $itemCarriergroups = array();

        foreach ($items as $item) {
            if ($item->getParentItem() || $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                continue;
            }
            $itemCarriergroups[] = Mage::helper('shipperhq_postorder')->getCarriergroupTitle($item);
        }

        return Mage::helper('core')->jsonEncode($itemCarriergroups);
    }

    public function getBundleCarriergroupText()
    {
        $items = $this->getItemsCollection();
        $itemCarriergroups = array();

        foreach ($items as $item) {
            if (!$item->getParentItem() || $item->getParentItem()->getProductType() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                continue;
            }
            // child of bundle
            $itemCarriergroups[] = Mage::helper('shipperhq_postorder')->getCarriergroupTitle($item);
        }

        return Mage::helper('core')->jsonEncode($itemCarriergroups);
    }

    public function getItemShippingInformation()
    {
        $items = $this->getItemsCollection();
        $itemsShippingInformation = array();

        foreach ($items as $item) {
            //pickup_chosen is deprecated - leaving in place to historical orders.
            if(!is_null($item->getPickupChosen()) && $item->getPickupChosen() != '') {
                $itemsShippingInformation[] = str_replace("'", '',$item->getPickupChosen());
            }
            elseif(!is_null($item->getCarriergroupShipping()) && $item->getCarriergroupShipping() != '') {
                $itemsShippingInformation[] = str_replace("'", '',$item->getCarriergroupShipping());
            }
            else {
                $itemsShippingInformation[] = '';
            }
        }
        return Mage::helper('core')->jsonEncode($itemsShippingInformation);
    }
}