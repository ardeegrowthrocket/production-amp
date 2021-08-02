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



class Mirasvit_Advr_Block_Adminhtml_Block_Grid_Renderer_PostAction extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    static private $rowCounter = 0;

    public function render(Varien_Object $row)
    {
        $block = $this->getLayout()->createBlock('core/template');
        $block->setTemplate('mst_advr/block/post_form.phtml');
        $block->setColumn($row);

        self::$rowCounter++;
        $block->setId(self::$rowCounter);

        return $block->toHtml();
    }
}
