<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/extension_advr
 * @version   1.2.13
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



class Mirasvit_Advr_Model_System_Config_Source_Status extends Varien_Object
{
    public function toOptionArray()
    {
        $result = array();
        foreach (Mage::getSingleton('sales/order_config')->getStatuses() as $value => $label) {
            $result[] = array(
                'label' => $label,
                'value' => $value
            );
        }

        return $result;
    }

    public function toOptionHash()
    {
        $result = array();
        foreach (Mage::getSingleton('sales/order_config')->getStatuses() as $value => $label) {
            $result[$value] = $label;
        }

        return $result;
    }

    public function getListtoString()
    {
        $result = array();
        foreach (Mage::getSingleton('sales/order_config')->getStatuses() as $value => $label) {
            $result[] = $value;
        }

        return implode(',', $result);
    }
}
