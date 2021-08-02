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
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';
class Shipperhq_Postorder_Adminhtml_Postorder_Sales_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{
    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     */
    public function shipAction()
    {
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }
        try {
            if ($shipment = $this->_initShipment()) {
              //  $shipment->register();
                Mage::helper('shipperhq_postorder/email')->registerShipment($shipment);
                $comment = '';
                if (!empty($data['comment_text'])) {
                    $shipment->addComment($data['comment_text'], isset($data['comment_customer_notify']));
                    if (isset($data['comment_customer_notify'])) {
                        $comment = $data['comment_text'];
                    }
                }
                if (!empty($data['send_email'])) {
                    $shipment->setEmailSent(true);
                }
                $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $shipment->setSplitShippedStatus(Shipperhq_Postorder_Model_Shipping_Carrier_Source_ShipStatus::SHIPPERHQ_SHIPSTATUS_SHIPPED);
                $this->_saveShipment($shipment);
                $shipment->sendEmail(!empty($data['send_email']), $comment);
                $this->_getSession()->addSuccess($this->__('The shipment has been created.'));
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
                $this->_redirect('adminhtml/sales_order_shipment/view', array('shipment_id' => $this->getRequest()->getParam('shipment_id')));
                return;
            } else {
                $this->_forward('noRoute');
                return;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Cannot save shipment.'));
        }
        $this->_redirect('adminhtml/sales_order/new', array('order_id' => $this->getRequest()->getParam('order_id')));
    }

    public function emailwareAction()
    {
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        if ($shipmentId) {
            Mage::helper('shipperhq_postorder/email')->sendNewShipmentEmail($shipmentId);
            $this->_getSession()->addSuccess($this->__('The email has been sent.'));
        } else {
            $this->_getSession()->addError($this->__('Cannot email origin.'));
            $this->_redirect('adminhtml/sales_order/new', array('order_id' => $this->getRequest()->getParam('order_id')));
        }
        $this->_redirect('adminhtml/sales_order_shipment/view', array('shipment_id' => $shipmentId));
    }

}