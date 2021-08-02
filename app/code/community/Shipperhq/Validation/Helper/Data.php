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

class  Shipperhq_Validation_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function validateAtAddressBook()
    {
       return Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation',
            'carriers/shipper/AV_ENABLED') && Mage::getStoreConfig('carriers/shipper/AV_VALIDATE_ACCOUNT');
    }

    public function validateAtCheckout()
    {
        return Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Validation',
            'carriers/shipper/AV_ENABLED') && Mage::getStoreConfig('carriers/shipper/AV_VALIDATE_CHECKOUT');

    }

    public function getValidatedAddressDisplay($validResult)
    {
        $output = '';
        $text = Mage::getModel('shipperhq_validation/validator_result')->getWording($validResult);
        $imageURL = Mage::getModel('shipperhq_validation/validator_result')->getSymbol($validResult);
        $output .= '<br/><div><img src="'. Mage::getDesign()->getSkinUrl($imageURL) .'" width="16" height="16" alt="" />'.
            $text .'</div>';

        return $output;
    }

    public function showAddressSelector()
    {
        return true;
    }

    /*
     * Set result to NOT_VALIDATED so validation is performed again
     */
    public function checkAddressValidResult($validResult)
    {
        switch($validResult){
            case '':
                $validResult = Shipperhq_Validation_Model_Validator_Result::NOT_VALIDATED;
                break;
        }
        return $validResult;
    }

    public function shouldRevalidate($validResult)
    {
        $revalidate = true;
        switch($validResult) {
            case Shipperhq_Validation_Model_Validator_Result::ERROR:
                $revalidate = true;
                break;
            case Shipperhq_Validation_Model_Validator_Result::VALID:
                $revalidate = false;
                break;
            case Shipperhq_Validation_Model_Validator_Result::INVALID:
                $revalidate = false;
                break;
            case Shipperhq_Validation_Model_Validator_Result::CUSTOMER_OVERRIDE:
                $revalidate = false;
                break;
            case Shipperhq_Validation_Model_Validator_Result::MANUAL_OVERRIDE:
                $revalidate = false;
                break;
            default:
                $revalidate = true;
                break;
        }
        return $revalidate;
    }

    public function getAddressValidatedResult($addressId)
    {
        $address = $this->getCustomerAddress($addressId);
        if($address->getId()) {
            return $address->getAddressValid();
        }
        return false;
    }

    public function getCustomerAddress($addressId)
    {
        $customerAddress = Mage::getModel('customer/address')->load($addressId);
        return $customerAddress;

    }

}