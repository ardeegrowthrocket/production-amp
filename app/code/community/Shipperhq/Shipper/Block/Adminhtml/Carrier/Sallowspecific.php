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

class Shipperhq_Shipper_Block_Adminhtml_Carrier_Sallowspecific
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * We only want to show this option for legacy customers who have already turned the switch on.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return bool
     */
    public function isHidden(Varien_Data_Form_Element_Abstract $element) {
        // For option values see: Mage_Adminhtml_Model_System_Config_Source_Shipping_Allspecificcountries

        return $element->getValue() == 0;
    }



    /**
     * Decorate field row html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml($element, $html)
    {
        $style = $this->isHidden($element) ? "style=\"display:none\"" : "";
        return '<tr id="row_' . $element->getHtmlId() . '" ' . $style . '>' . $html . '</tr>';
    }
}