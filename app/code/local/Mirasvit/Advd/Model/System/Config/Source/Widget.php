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



class Mirasvit_Advd_Model_System_Config_Source_Widget
{
    public function toOptionArray()
    {
        $result = array();
        $widgets = Mage::getSingleton('advd/config')->getWidgets();

        $result[''] = Mage::helper('advd')->__('Please select widget');

        foreach ($widgets as $code => $widget) {
            $group = $widget->getGroup();

            if (!isset($result[$group])) {
                $result[$group] = array(
                    'label' => $group,
                    'value' => array(),
                );
            }

            $result[$group]['value'][] = array(
                'value' => $code,
                'label' => $widget->getName(),
            );
        }

        return $result;
    }
}
