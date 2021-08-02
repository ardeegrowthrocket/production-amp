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


class Shipperhq_Postorder_Block_Adminhtml_Sales_Order_View_Drinfo extends Mage_Adminhtml_Block_Sales_Order_View_Info//Mage_Adminhtml_Block_Sales_Order_Abstract
{
    public function getCarriergroupInfoHtml()
    {
        $order = $this->getOrder();
        $htmlOutput='';
        $encodeShipDetails = $order->getCarriergroupShippingDetails();
        $carriergroupText='';
        if($order->getConfirmationNumber() != '') {
            $carriergroupText .=  'Order confirmation number: ' .$order->getConfirmationNumber() .'<br/>';
        }
        $carriergroupText.= Mage::helper('shipperhq_shipper')->getCarriergroupShippingHtml($encodeShipDetails, $order);
        $deliveryComments = $order->getShqDeliveryComments();

        $cginfo = Mage::helper('shipperhq_shipper')->decodeShippingDetails($order->getCarriergroupShippingDetails());
        if (!empty($cginfo)) {
            $htmlOutput = '<div class="box-right-origin-info"><div class="entry-edit">';
            $htmlOutput.= '<div class="entry-edit-head">';
            $htmlOutput.= '<h4 class="icon-head head-shipping-method">';
            $cgrp = $cginfo[0];
            if($desc = Mage::getStoreConfig(Shipperhq_Shipper_Helper_Data::SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH)) {
                $heading = $desc;
            } else {
                $heading = $cgrp['mergedDescription'];
            }
            $heading = $heading .' ' .Mage::helper("shipperhq_postorder")->__("Shipping Information");
            $htmlOutput.= $heading;
            $htmlOutput.= '</h4>';
            $htmlOutput.= '</div><fieldset>';
            $htmlOutput.= $carriergroupText;
            if(!empty($deliveryComments)){
                $htmlOutput.= Mage::helper('shipperhq_postorder')->__('Delivery Comments') .' : ' . $order->getShqDeliveryComments();
            }
            $htmlOutput.= '</fieldset></div></div>';

        } else if (!empty($deliveryComments)) {
            $htmlOutput = '<div class="box-right"><div class="clear"></div><div class="entry-edit">';
            $htmlOutput.= '<div class="entry-edit-head">';
            $htmlOutput.= '<h4 class="icon-head head-shipping-method">';
            $heading = Mage::helper("shipperhq_postorder")->__("Shipping Information");
            $htmlOutput.= $heading;
            $htmlOutput.= '</h4>';
            $htmlOutput.= '</div><fieldset>';
            $htmlOutput .= Mage::helper('shipperhq_postorder')->__('Delivery Comments') .' : ' . $order->getShqDeliveryComments();
            $htmlOutput.= '</fieldset> <div class="clear"/></div></div>';
        }
        return $htmlOutput;
    }

}