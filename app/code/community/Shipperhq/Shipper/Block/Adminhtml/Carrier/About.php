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
class Shipperhq_Shipper_Block_Adminhtml_Carrier_About extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $logo = $this->getSkinUrl('shipperhq/images/shipperhq_logo.png');
        $docs = $this->getSkinUrl('shipperhq/images/docs_logo.png');
        $additionalModules = $this->getAdditionalModulesOutput();
        $html = '<div style="padding:30px;background-color:#fff;border-radius:5px;border:1px solid #efefef ;margin-bottom:12px;overflow:auto;">
    <div style="width:68%;float:left;text-align:left;">
        <img src="' .$logo .'" style="max-width: 198px;margin-bottom:22px;">
        <p style="margin-bottom:12px;font-size:15px;">This extension connects Magento to ShipperHQ, a powerful, easy-to-use eCommerce shipping management platform</p>
        <p style="margin-bottom:18px;font-size:12px;">If you have questions about ShipperHQ or need support, visit <a href="http://www.ShipperHQ.com" target="_blank">ShipperHQ.com</a>. ShipperHQ is a product of <a href="http://www.webshopapps.com" target="_blank">WebShopApps</a>, developers of powerful shipping solutions for Magento.</p>
        <p style="margin-bottom:12px;font-size:12px"><a href="' .$this->getUrl('adminhtml/shqsynchronize') .'">Synchronize with ShipperHQ</a></p></div>    
   <div style="width:25%;float:right;text-align:center;">
        <div style="background: #f9f9f9 ;border:1px solid #efefef ;margin-bottom:20px;padding:10px;">Installed Version <strong style="color:#00aae5 ">'.$additionalModules.'</strong></div>
        <a href="http://docs.shipperhq.com" target="_blank" style="text-decoration: none;">
        <div style="background: #f9f9f9 ;border:1px solid #efefef ;margin-bottom:12px;padding:15px;text-decoration: none;">
            <img src="' .$docs .'" style="width:42px;height:42px;margin:0 auto 12px auto;display:block;" data-pin-nopin="true">
            <strong style="font-weight:bold;text-decoration:none;color:#f77746 ;">ShipperHQ Help Docs</strong><br><p style="font-size:12px;color:#555;text-decoration: none;">Documentation &amp; Examples</p>
        </div></a>
    </div></div>';
        return $html;
    }

    protected function getModuleVersion() {
        return (string) Mage::getConfig()->getNode('modules/Shipperhq_Shipper/extension_version');
    }


    private function getAdditionalModulesOutput()
    {
        $output = '';
        $additionalModules = Mage::helper('shipperhq_shipper/module')->getInstalledModules(true);
        foreach ($additionalModules as $moduleName => $version) {
            $output .= '<div style="margin-bottom:6px;"><strong>'.$moduleName.'</strong>';
            if ($version !== '') {
                $output .= ': '.$version.'';
            }
            $output.= '</div>';
        }
        return $output;
    }
}