<?php

/**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_shipperhq
 * User         Joshua Stewart
 * Date         22/07/2014
 * Time         16:17
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */

class Shipperhq_Postorder_Helper_Email extends Mage_Core_Helper_Abstract
{

    const XML_SHIPPERHQ_PATH_EMAIL_TEMPLATE               = 'sales_email/shipperhq_postorder_order/template';
    const XML_SHIPPERHQ_PATH_EMAIL_IDENTITY               = 'sales_email/shipperhq_postorder_order/identity';
    const XML_SHIPPERHQ_PATH_EMAIL_COPY_TO                = 'sales_email/shipperhq_postorder_order/copy_to';
    const XML_SHIPPERHQ_PATH_EMAIL_COPY_METHOD            = 'sales_email/shipperhq_postorder_order/copy_method';
    const XML_SHIPPERHQ_PATH_EMAIL_ENABLED                = 'sales_email/shipperhq_postorder_order/enabled';

    CONST SHIPPERHQ_SEND_EMAIL_ORDER = 'order_placed';
    CONST SHIPPERHQ_SEND_EMAIL_INVOICE = 'invoice_placed';
    CONST SHIPPERHQ_SEND_EMAIL_NEVER = 'never';

    private $_order;
    private $_storeId;
    private $_orderId;

    /**
     * sales_order_save_after
     */
    public function salesOrderSaveAfter($observer)
    {
        $eventName = $observer->getEvent()->getName();
        if ($eventName == 'sales_order_place_after') {
            $order = $observer->getEvent()->getOrder();
        } else {
            $invoice = $observer->getEvent()->getInvoice();
            $orderId = $invoice->getOrder()->getIncrementId();
            $order = $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

            /**
             * If this a new invoice, or a comment on existing? Magento throws same event
             */
            if ($invoice->getUpdatedAt() != $invoice->getCreatedAt()) {
                return null;
            }
        }

        if($order->getIsVirtual()) { //DROP-91
            return null;
        }

        $this->setOrder($order);

        // get the carriergroups in the order
        $encDetails = $order->getCarriergroupShippingDetails();
        if(is_null($encDetails) || empty($encDetails) && !is_null($order->getQuote())) {
            $encDetails = $order->getQuote()->getShippingAddress()->getCarriergroupShippingDetails();
        }

        if (!empty($encDetails)) {

            $shipDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails($encDetails);
            foreach ($shipDetails as $shipItem) {
                $sendEmail = $shipItem['emailOption'];
                $pickupEmail = array_key_exists('pickup_email_option', $shipItem) ? $shipItem['pickup_email_option'] : null;
                $shipment = null;

                if (($sendEmail == self::SHIPPERHQ_SEND_EMAIL_ORDER && $eventName == 'sales_order_place_after') ||
                    ($sendEmail == self::SHIPPERHQ_SEND_EMAIL_INVOICE && $eventName == 'sales_order_invoice_save_after')) {
                    $carriergroupId = $shipItem['carrierGroupId'];
                    if (!isset($carriergroupId)){
                        continue; //should never be in here
                    }

                    //create shipments for each warehouse
                    $shippingDescription = $this->buildShippingDescription($shipItem, $order);

                    $emailDetails = $this->getEmailDetails($shipItem);

                    if(!$emailDetails['manualship']){
                        $shipment = $this->createShipment($order,$shipItem,$shippingDescription);
                        if($order->getManualShip() != 1){
                            $order->setManualShip(2);
                        }
                    } else {
                        $order->setManualShip(1);
                    }

                    if ($sendEmail && !$emailDetails['manualship'] && $shipment) {
                        $this->sendFullShipmentEmail($shipItem,$shipment,$order);
                    }
                }

                if (($pickupEmail == self::SHIPPERHQ_SEND_EMAIL_ORDER && $eventName == 'sales_order_place_after') ||
                    ($pickupEmail == self::SHIPPERHQ_SEND_EMAIL_INVOICE && $eventName == 'sales_order_invoice_save_after')) {

                    $carriergroupId = $shipItem['carrierGroupId'];

                    if (!isset($carriergroupId)) {
                        continue; //should never be in here
                    }

                    $shippingDescription = $this->buildShippingDescription($shipItem, $order);

                    if (!isset($shipment)) {
                        $shipment = $this->createShipment($order, $shipItem, $shippingDescription);
                    }

                    if ($pickupEmail && $shipment) {
                        $this->sendFullShipmentEmail($shipItem,$shipment,$order,false,"",true);
                    }
                }
            }
        } else {
            // Review action here - as all orders should have carriergroup shipping details
        }
    }

    private function buildShippingDescription($shipItem, $order)
    {
        $shippingDescription = $shipItem['carrierTitle'].' - '. $shipItem['methodTitle'].' ';
        $text = $order->formatPrice($shipItem['price']);
        $shippingDescription .= " " .$text .'<br/>';
        if(array_key_exists('pickup_date', $shipItem)) {
            $shippingDescription .= Mage::helper('shipperhq_shipper')->__('Location') .' : ' .$shipItem['location_name'];
            $shippingDescription .= Mage::helper('shipperhq_shipper')->__(' Date') .' : ' .$shipItem['pickup_date'];
            if(array_key_exists('pickup_slot', $shipItem)) {
                $shippingDescription .= Mage::helper('shipperhq_shipper')->__(' Time : ') .str_replace('_', ' - ', $shipItem['pickup_slot']);
            }
            $shippingDescription.= '<br/>';
        }
        else if(array_key_exists('delivery_date', $shipItem) && $shipItem['delivery_date'] != '') {
            $shippingDescription .= Mage::helper('shipperhq_shipper')->__(' Delivery Date') .' : ' .$shipItem['delivery_date'];
            if(array_key_exists('del_slot', $shipItem)  && $shipItem['del_slot'] != '') {
                $shippingDescription .= Mage::helper('shipperhq_shipper')->__(' Time : ') .$shipItem['del_slot'];
            }
            $shippingDescription.= '<br/>';
        }

        return $shippingDescription;
    }

    public function registerShipment($shipment)
    {

        if(!Mage::getStoreConfig('carriers/shipper/active',$shipment->getStoreId())) {
            return $shipment->register();
        }

        $totalQty = 0;
        foreach ($shipment->getAllItems() as $item) {
            if ($item->getQty()>0) {
                $item->register();
                if (!$item->getOrderItem()->isDummy(true)) {
                    $totalQty+= $item->getQty();
                }
            }
            else {
                $item->isDeleted(true);
            }
        }
        $shipment->setTotalQty($totalQty);

        return $shipment;
    }


    /**
     * Declare order for shipment
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  Mage_Sales_Model_Order_Shipment
     */
    private function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        $this->setOrderId($order->getId());
        $this->setStoreId($order->getStoreId());
    }

    /**
     * Retrieve the order the shipment for created for
     *
     * @return Mage_Sales_Model_Order
     */
    private function getOrder()
    {
        return $this->_order;
    }

    private function getStoreId() {
        return $this->_storeId;
    }

    private function setStoreId($storeId) {
        $this->_storeId = $storeId;
    }

    private function getOrderId() {
        return $this->_orderId;
    }

    private function setOrderId($orderId) {
        $this->_orderId = $orderId;
    }

    private function _initShipment($order,$carriergroupDetails) {
        if (!$order->canShip()) {
            return false;
        }
        if(!$shipment = $this->prepareShipment($carriergroupDetails))
        {
            return false;
        }
        $shipment->setSplitShippedStatus(Shipperhq_Postorder_Model_Shipping_Carrier_Source_ShipStatus::SHIPPERHQ_SHIPSTATUS_PENDING);

        $shipment->setCarriergroup($carriergroupDetails['carrierGroupId']);

        return $shipment;
    }

    /**
     * Prepare order shipment based on order items and requested items qty
     *
     * @param       $warehouse
     * @param array $qtys
     * @internal param array $data
     * @return Mage_Sales_Model_Order_Shipment
     */
    private function prepareShipment($carriergroupDetails, $qtys = array())
    {
        $totalQty = 0;
        $shipment = Mage::getModel('sales/convert_order')->toShipment($this->_order);
        $itemIdsAdded = array();

        $assignedCarrierGroupId = '';
        if(array_key_exists('carrierGroupId', $carriergroupDetails)) {
            $assignedCarrierGroupId = $carriergroupDetails['carrierGroupId'];
        }

        foreach ($this->_order->getAllItems() as $orderItem) {
            if (!$this->_canShipItem($orderItem, $qtys)) {

                continue;
            }

            $itemCarriergroup = $orderItem->getCarriergroupId();
            if ($assignedCarrierGroupId !=$itemCarriergroup) {
                continue;
            }

            $emailDetails = $this->getEmailDetails($carriergroupDetails);
            if($emailDetails['manualship']){
                continue;
            }

            $item = Mage::getModel('sales/convert_order')->itemToShipmentItem($orderItem);

            $itemIdsAdded[] = $orderItem->getId();

            if($orderItem->getParentItem()){
                if(!in_array($orderItem->getParentItemId(), $itemIdsAdded)){
                    $itemIdsAdded[] = $orderItem->getParentItemId();
                    $sitem = Mage::getModel('sales/convert_order')->itemToShipmentItem($orderItem->getParentItem());
                    $sitem->setQty(1);
                    $totalQty += 0;
                    $shipment->addItem($sitem);
                }
            }

            if ($orderItem->isDummy()) {
                $qty = 0;
            } else {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = min($qtys[$orderItem->getId()], $orderItem->getQtyToShip());
                } elseif (!count($qtys)) {
                    $qty = $orderItem->getQtyToShip();
                } else {
                    continue;
                }
            }

            $totalQty += $qty;
            $item->setQty($qty);
            // $orderItem->setLockedDoShip(true);
          //  $orderItem->save();

            $shipment->addItem($item);

        }
        if($totalQty == 0) {
            return false;
        }
        $shipment->setTotalQty($totalQty);
        return $shipment;
    }

    /**
     * Check if order item can be shiped. Dummy item can be shiped or with his childrens or
     * with parent item which is included to shipment
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param array $qtys
     * @return bool
     */
    protected function _canShipItem($item, $qtys=array())
    {
        if ($item->getIsVirtual() || $item->getLockedDoShip()) {
            return false;
        }
        if ($item->isDummy(true)) {
            if ($item->getHasChildren()) {
                if ($item->isShipSeparately()) {
                    return true;
                }
                foreach ($item->getChildrenItems() as $child) {
                    if ($child->getIsVirtual()) {
                        continue;
                    }
                    if (empty($qtys)) {
                        if ($child->getQtyToShip() > 0) {
                            return true;
                        }
                    } else {
                        if (isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) {
                            return true;
                        }
                    }
                }
                return false;
            } else if($item->getParentItem()) {
                $parent = $item->getParentItem();
                if (empty($qtys)) {
                    return $parent->getQtyToShip() > 0;
                } else {
                    return isset($qtys[$parent->getId()]) && $qtys[$parent->getId()] > 0;
                }
            }
        } else {
            return $item->getQtyToShip()>0;
        }
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return $this
     */
    private function _saveShipment($shipment)
    {
        // $shipment->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    // sendOrderNotificationEmail Model/Vendor.php
    // updateOrderItemsVendors Data.php multi extn

    private function createShipment($order,$carriergroupDetails,$shippingDescription)
    {
        $shipment = false;
        try {
            if ($shipment = $this->_initShipment($order,$carriergroupDetails)) {
                //$shipment->register(); This is done in shipmentController.php
                $shipment->setShippingDescription($shippingDescription);

                $this->_saveShipment($shipment);
            } else {
                //TODO $this->_forward('noRoute');
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());

        } catch (Exception $e) {
            Mage::log('Cannot save shipment.');
            Mage::log($e->getMessage());
            //  throw ($e); // never throw exception
        }
        return $shipment;
    }

    private function _getEmails($configPath)
    {
        $data = Mage::getStoreConfig($configPath, $this->getStoreId());
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }

    //   public function sendNewShipmentEmail(Mage_Sales_Model_Order_Shipment $shipment) {
    public function sendNewShipmentEmail($shipmentId) {
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);

        if ($shipment==NULL) {
            return;
        }
        $carriergroup = $shipment->getCarriergroup();
        $carriergroupDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails(
            $shipment->getOrder()->getCarriergroupShippingDetails());
        $ourCarriergroupDetails = false;
        foreach($carriergroupDetails as $carriergroupDetail) {
            if($carriergroupDetail['carrierGroupId'] == $carriergroup) {
                $ourCarriergroupDetails = $carriergroupDetail;
                break;
            }
        }

        if ($ourCarriergroupDetails) {
            $this->sendFullShipmentEmail($ourCarriergroupDetails,$shipment,$shipment->getOrder(),true);
        }
    }

    /**
     * Sending email with Invoice data
     *
     * @param        $warehouse
     * @param        $shipment
     * @param        $order
     * @param bool   $overrideManual
     * @param string $comment
     * @return Mage_Sales_Model_Order_Invoice
     */
    private function sendFullShipmentEmail($carriergroupDetails, $shipment, $order, $overrideManual = false, $comment = '', $sendPickupEmail = false)
    {
        if ($sendPickupEmail){
            $carriergroupContactDetails = $this->getPickupEmailDetails($carriergroupDetails);

            if ($carriergroupContactDetails['pickup_email'] == "") {
                return $this;
            }
        } else {
            $carriergroupContactDetails = $this->getEmailDetails($carriergroupDetails);

            if ($carriergroupContactDetails['email'] == "" || (!$overrideManual && $carriergroupContactDetails['manualmail'])) {
                return $this;
            }
        }

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);
        $mailTemplate = Mage::getModel('core/email_template');

        $copyTo = $this->_getEmails(self::XML_SHIPPERHQ_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_SHIPPERHQ_PATH_EMAIL_COPY_METHOD, $this->getStoreId());

        if ($copyTo && $copyMethod == 'bcc') {
            foreach ($copyTo as $email) {
                $mailTemplate->addBcc($email);
            }
        }

        $template = Mage::getStoreConfig(self::XML_SHIPPERHQ_PATH_EMAIL_TEMPLATE, $this->getStoreId());

        $sendTo = array();
        if ($sendPickupEmail) {
            $emailAdd = preg_split('/,/', $carriergroupContactDetails['pickup_email']);
            $contactName = $carriergroupContactDetails['pickup_contact'];
        } else {
            $emailAdd = preg_split('/,/', $carriergroupContactDetails['email']);
            $contactName = $carriergroupContactDetails['contact'];
        }

        foreach ($emailAdd as $email) {

            $sendTo[] = array (
                'email' => $email,
                'name'  => $contactName
            );
        }

        if ($copyTo && ($copyMethod == 'copy')) {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'name'  => null,
                    'email' => $email
                );
            }
        }

        $attachPdf = Mage::getStoreConfig('sales_email/shipperhq_postorder_order/send_pdf');

        if($attachPdf)
        {
            $shipmentArray = array($shipment);
            $pdfShipment = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipmentArray);
        }

        foreach ($sendTo as $recipient) {
            if($attachPdf){
                $this->addAttachment($mailTemplate,$pdfShipment,$shipment->getIncrementId().'.pdf');
            }

            $dispatchDate = array_key_exists('dispatch_date', $carriergroupDetails) ? $carriergroupDetails['dispatch_date'] : $order->getDispatchDate();
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$order->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig(self::XML_SHIPPERHQ_PATH_EMAIL_IDENTITY, $order->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'order'             => $order,
                        'shipment'          => $shipment,
                        'comment'           => $comment,
                        'billing'           => $order->getBillingAddress(),
                        'carriergroup'	    => $carriergroupDetails['checkoutDescription'],
                        'carriergroupname'	=> $sendPickupEmail ? $carriergroupDetails['pickup_contact'] : $carriergroupDetails['contactName'],
                        'dispatchdate'	    => $dispatchDate,
                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }

    public function addAttachment($mailTemplate, $pdf, $filename) {
        $file=$pdf->render();
        $attachment = $mailTemplate->getMail()->createAttachment($file);
        $attachment->type = 'application/pdf';
        $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = Zend_Mime::ENCODING_BASE64;
        $attachment->filename = $filename;
    }

    private function getEmailDetails($carriergroupDetailArray) {

        $emailDetails=array(
            'email' 		=> $carriergroupDetailArray['emailAddress'],
            'contact' 		=> $carriergroupDetailArray['contactName'],
            'manualmail' 	=> false,
            'manualship' 	=> false,
        );
        return $emailDetails;
    }

    private function getPickupEmailDetails($carriergroupDetailArray) {

        $emailDetails=array(
            'pickup_email' 		=> $carriergroupDetailArray['pickup_email'],
            'pickup_contact' 	=> $carriergroupDetailArray['pickup_contact'],
        );
        return $emailDetails;
    }
}