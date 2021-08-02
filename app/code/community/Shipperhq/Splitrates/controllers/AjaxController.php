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
class Shipperhq_Splitrates_AjaxController extends Mage_Core_Controller_Front_Action
{

    protected static $_debug;

    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'false')
            ->sendResponse();
        return $this;
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        $action = $this->getRequest()->getActionName();

        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true)
            && !in_array($action, array('index', 'progress', 'oscSaveShipping'))) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

     public function oscSaveShippingAction()
     {
         if ($this->_expireAjax()) {
             return;
         }

         self::$_debug = Mage::helper('shipperhq_shipper')->isDebug();

         if ($this->getRequest()->isPost()) {
             $carriergroupId = $this->getRequest()->getParam('carriergroup_id');
             $selectedMethod = $this->getRequest()->getParam('selected_method');
             $quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage();
             $shipMethodsCarrierGroupsSelect = $quoteStorage->getCarriergroupSelected();
             if(is_null($shipMethodsCarrierGroupsSelect)) {
                 $shipMethodsCarrierGroupsSelect = array();
             }
             $shipMethodsCarrierGroupsSelect[$carriergroupId] = $selectedMethod;
             $quoteStorage->setCarriergroupSelected($shipMethodsCarrierGroupsSelect);
         }

     }
}