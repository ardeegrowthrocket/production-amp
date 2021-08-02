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
class Shipperhq_Postorder_Model_Observer extends Mage_Core_Model_Abstract
{

    public function saveOrderAfter($observer)
    {
        try {
            if (!Mage::helper('shipperhq_postorder')->isActive()) {
                return;
            }
            $eventName = $observer->getEvent()->getName();

            switch ($eventName) {
                case 'sales_order_place_after':
                    $order = $observer->getEvent()->getOrder();
                    if(Mage::helper('shipperhq_shipper')->useDefaultCarrierCodes()) {
                        $quote = $order->getQuote();
                        $shipping_method = $order->getShippingMethod();
                        $rate = $quote->getShippingAddress()->getShippingRateByCode($shipping_method);
                        if($rate) {
                            $order->setCarrierType($rate->getCarrierType());
                            list($carrierCode, $method) = explode('_', $shipping_method, 2);
                            $magentoCarrierCode = Mage::helper('shipperhq_shipper')
                                ->mapToMagentoCarrierCode($rate->getCarrierType(),$carrierCode);
                            $magentoMethodCode = Mage::helper('shipperhq_shipper')->getMagentoUpsMethodCode($method);

                            if($magentoMethodCode) { //SHQ16-2444
                                $method = $magentoMethodCode;
                            }

                            $newShipMethod = ($magentoCarrierCode .'_' .$method);
                            $order->setShippingMethod($newShipMethod);
                        }
                    }
                    break;
                case 'sales_order_invoice_save_after':
                    $order = $observer->getEvent()->getInvoice()->getOrder();
                    break;
                default:
                    $order = '';
            }
            $carriergroupDetail = $order->getCarriergroupShippingDetails();
            if (!Mage::helper('shipperhq_postorder')->isCreateShipmentEmail($carriergroupDetail)) {
                return null;
            }

            // Send emails to all the origins involved
            Mage::helper('shipperhq_postorder/email')->salesOrderSaveAfter($observer);


        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    //TEST
    /**
     * Prepend an additional order info box to the gift options block.
     * - event: core_block_abstract_to_html_after
     *
     * @param Varien_Event_Observer $observer
     */
    public function insertAdminBlock(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('shipperhq_postorder')->isActive()) {
            return;
        }

        // >> check for adminhtml/sales_order/view route here <<
        if(Mage::app()->getRequest()->getControllerName() != 'sales_order'
            || Mage::app()->getRequest()->getActionName() != 'view')
        {
            return;
        }
        $thisBlock = $observer->getBlock();
        if ($thisBlock->getNameInLayout() == 'gift_options') {

            $carrierGroupInfo = Mage::app()->getLayout()->createBlock(
                'shipperhq_postorder/adminhtml_sales_order_view_drinfo',
                'postorder_info',
                array(
                    'template' => 'shipperhq/postorder/sales/order/view/carriergroup_info.phtml',
                    'order' => Mage::registry('current_order'),
                )
            );

            $cgHtml = $carrierGroupInfo->getCarriergroupInfoHtml();
            $giftOptionsHtml = $observer->getTransport()->getHtml();
            $observer->getTransport()->setHtml($cgHtml . $giftOptionsHtml);

        }
    }
}