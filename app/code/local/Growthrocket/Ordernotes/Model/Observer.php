<?php

class Growthrocket_Ordernotes_Model_Observer
{

    /**
     * Save order note to sales quote
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function saveOrderNotes(Varien_Event_Observer $observer)
    {
        $orderNotes = Mage::app()->getRequest()->getParam('order_notes');
        if (!empty($orderNotes)) {
            Mage::getSingleton("checkout/cart")->getQuote()->setOrderNotes($orderNotes)->save();
            Mage::getSingleton("checkout/session")->getQuote()->setOrderNotes($orderNotes)->save();

            $order = $observer->getEvent()->getOrder();
            $order->setOrderNotes($orderNotes);
            $order->save();
        }

        return $this;
    }


    public function getSalesOrderViewInfo(Varien_Event_Observer $observer) {
        $block = $observer->getBlock();
        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('ordernotes.order.info.custom.block'))) {
            $transport = $observer->getTransport();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }
}