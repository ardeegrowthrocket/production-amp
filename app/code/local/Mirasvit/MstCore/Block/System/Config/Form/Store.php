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
 * @package   mirasvit/extension_mcore
 * @version   1.0.24
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */


class Mirasvit_MstCore_Block_System_Config_Form_Store extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_dummyElement;
    protected $_fieldRenderer;
    protected $_values;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return '<div id="' . $element->getId() . '"></div>';
        return $html;
    }
}
