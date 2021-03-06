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



class Mirasvit_Advd_Model_System_Config_Source_Day
{
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('advr')->__('Sunday'),
                'value' => '0'
            ),
            array(
                'label' => Mage::helper('advr')->__('Monday'),
                'value' => '1'
            ),
            array(
                'label' => Mage::helper('advr')->__('Tuesday'),
                'value' => '2'
            ),
            array(
                'label' => Mage::helper('advr')->__('Wednesday'),
                'value' => '3'
            ),
            array(
                'label' => Mage::helper('advr')->__('Thursday'),
                'value' => '4'
            ),
            array(
                'label' => Mage::helper('advr')->__('Friday'),
                'value' => '5'
            ),
            array(
                'label' => Mage::helper('advr')->__('Saturday'),
                'value' => '6'
            ),
        );
    }
}
