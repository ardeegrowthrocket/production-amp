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
class Shipperhq_Validation_Model_Validator_Result extends Shipperhq_Shipper_Model_Source_Validation_Result
{

    public function getSymbol($result)
    {
        switch($result){
            case self::VALID:
                $symbol = 'images/ico_success.gif';
                break;
            case self::VALID_CORRECTED;
                $symbol = 'images/ico_success.gif';
                break;
            case self::AMBIGUOUS:
                $symbol =  'images/i_notice.gif';
                break;
            case self::CUSTOMER_OVERRIDE:
                $symbol =  'images/i_notice.gif';
                break;
            case self::MANUAL_OVERRIDE:
                $symbol =  'images/ico_success.gif';
                break;
            case self::INVALID:
                $symbol = 'images/error_msg_icon.gif';
                break;
            case self::NOT_VALIDATED:
                $symbol = 'images/error_msg_icon.gif';
                break;
            default:
                $symbol = 'images/error_msg_icon.gif';
                break;
        }
        return $symbol;
    }

    public function getWording($result)
    {
        switch($result){
            case self::NOT_VALIDATED:
                $text = Mage::helper('shipperhq_validation')->__(self::NOT_VALIDATED);
                break;
            case self::VALID:
                $text = Mage::helper('shipperhq_validation')->__(self::VALID);
                break;
            case self::VALID_CORRECTED:
                $text = Mage::helper('shipperhq_validation')->__(self::VALID_CORRECTED);
                break;
            case self::AMBIGUOUS:
                $text =  Mage::helper('shipperhq_validation')->__(self::AMBIGUOUS);
                break;
            case self::INVALID:
                $text = Mage::helper('shipperhq_validation')->__(self::INVALID);
                break;
            case self::CUSTOMER_OVERRIDE:
                $text = Mage::helper('shipperhq_validation')->__(self::CUSTOMER_OVERRIDE);
                break;
            case self::MANUAL_OVERRIDE:
                $text = Mage::helper('shipperhq_validation')->__(self::MANUAL_OVERRIDE);
                break;
            default:
                $text = Mage::helper('shipperhq_validation')->__(self::NOT_VALIDATED);
                break;
        }
        return $text;
    }

}