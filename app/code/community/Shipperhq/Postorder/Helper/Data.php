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

class Shipperhq_Postorder_Helper_Data extends Mage_Core_Helper_Abstract
{


    public function isActive()
    {
        return Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active');
    }

    public function getCarriergroupTitle($item)
    {
        if ($item->getCarriergroup() != '') {
            $title = $item->getCarriergroup();
        } else {
            $title = Mage::helper('shipperhq_postorder')->__('Shipping Group');
        }

        return $title;
    }


    public function getItemColumnTitle()
    {
         return Mage::getStoreConfig(Shipperhq_Shipper_Helper_Data::SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH);
    }

    /**
     * @param string $store
     * @return bool
     */
    public static function isCreateShipmentEmail($carriergroupDetail)
    {
        $detail = Mage::helper('shipperhq_shipper')->decodeShippingDetails($carriergroupDetail);
        $sendEmail = false;
        if(is_array($detail)) {
            foreach($detail as $cgdetail) {
                $emailOption = $cgdetail['emailOption'];
                $pickupEmailOption = array_key_exists('pickup_email_option', $cgdetail) ? $cgdetail['pickup_email_option'] : null;

                if($emailOption != '' && !is_null($emailOption) &&
                    $emailOption != Shipperhq_Postorder_Helper_Email::SHIPPERHQ_SEND_EMAIL_NEVER) {
                    $sendEmail = true;
                }

                if($pickupEmailOption != '' && !is_null($pickupEmailOption) &&
                    $pickupEmailOption != Shipperhq_Postorder_Helper_Email::SHIPPERHQ_SEND_EMAIL_NEVER) {
                    $sendEmail = true;
                }
            }
        }
        return $sendEmail;
    }
}

?>