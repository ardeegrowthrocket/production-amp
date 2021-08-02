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
 * @package   mirasvit/extension_asyncindex
 * @version   1.1.13
 * @copyright Copyright (C) 2017 Mirasvit (https://mirasvit.com/)
 */


/**
 * @category Mirasvit
 * @package  Mirasvit_AsyncIndex
 */
class Mirasvit_AsyncIndex_Model_System_Config_Source_ProcessingMode
{
    public function toOptionArray()
    {
        $result = array(
            array(
                'value' => Mirasvit_AsyncIndex_Model_Control::PROCESSING_MODE_PER_ENTITY,
                'label' => 'Each Entity',
            ),
            array(
                'value' => Mirasvit_AsyncIndex_Model_Control::PROCESSING_MODE_PER_INDEX,
                'label' => 'Each Index',
            ),
        );

        return $result;
    }
}
