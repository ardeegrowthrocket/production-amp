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
class Shipperhq_Pickup_Adminhtml_Shipperhq_Pickup_LocationController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var array
     */
    protected $_publicActions = array('rateLocation');
    
    public function rateLocationAction()
    {
        Mage::helper('shipperhq_shipper')->setQuote($this->_getQuote());
        
        if ($this->getRequest()->isGet()) {
            $response = Mage::getSingleton('shipperhq_pickup/service_location')->findLocations(
                $this->_getQuote(), $this->getRequest()->getParams()
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        } else {
            // If someone sends post?
            $this->getResponse()->setBody('{}');
        }
    }
    
    /**
     * Retrieve quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::getSingleton('adminhtml/session_quote')->getQuote();
    }

    // Contributed by Chevy Arora
    protected function _isAllowed()
    {
        return true;
    }

}