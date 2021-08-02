<?php
/**
 * Magento
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
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
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
class Shipperhq_Validation_CheckoutController extends Mage_Core_Controller_Front_Action
{

    protected $debug;

    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
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

        if (!in_array($action, array('getAddressValidResult', 'oscValidateAddress'))) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    public function getAddressValidResultAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation', 'carriers/shipper/AV_ENABLED')) {
            return;
        }

        $result = array();
        if($this->getRequest()->isGet()) {

            $data = $this->getRequest()->getParams();

            $addressId = $data['address_id'];
            if(!empty($data) && $addressId != '') {
                $result['valid_result'] = Mage::helper('shipperhq_validation')->getAddressValidatedResult((int)$addressId);
            }
            else {
                $result['valid_result'] = Shipperhq_Validation_Model_Validator_Result::NOT_VALIDATED;
            }
        }
        if(empty($result)) {
            return;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

    }

    public function oscValidateAddressAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation', 'carriers/shipper/AV_ENABLED')) {
            return;
        }
        $results = array('outcome' =>'');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $originalParams = $request->getParams();
            $billing_data = $request->getPost('billing', array());
            $shipping_data = $request->getPost('shipping', array());
            $customerAddressId = $request->getPost('billing_address_id', false);
            $shippingAddressId = $request->getPost('shipping_address_id', false);
            $address_valid = '';
            if(!empty($billing_data['use_for_shipping'])) {
                if(!empty($customerAddressId)) {
                    $address = Mage::helper('shipperhq_validation')->getCustomerAddress($customerAddressId);
                    $address_valid = $address->getAddressValid();
                }
                else {
                    $address = $billing_data;
                    $address_valid = array_key_exists('address_valid', $address) ? $address['address_valid'] : '';
                }
            }
            else {
                if(!empty($shippingAddressId)) {
                    $address = Mage::helper('shipperhq_validation')->getCustomerAddress($shippingAddressId);
                    $address_valid = $address->getAddressValid();
                }
                else {
                    $address = $shipping_data;
                    $address_valid = array_key_exists('address_valid', $address) ? $address['address_valid'] : '';
                }

            }

            if(is_object($address) || ($address['city'] != "" && $address['street'][0] != "")){
                if(Mage::helper('shipperhq_validation')->shouldRevalidate($address_valid)) {
                    $validator = Mage::getModel('shipperhq_validation/validator');
                    if($validator) {
                        $results = $validator->validateAddress($address);
                        $results['html'] = $validator->getResultDisplay($results,'', $this->getLayout());
                    }
                }
                else {
                    $results['outcome'] = '';
                }
            }
        }
        if(empty($results)) {
            return;
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($results));

    }


}