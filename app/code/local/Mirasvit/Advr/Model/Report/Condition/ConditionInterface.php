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




interface Mirasvit_Advr_Model_Report_Condition_ConditionInterface
{
    /**
     * Provide conditions used for joining table.
     *
     * @param Mirasvit_Advr_Model_Report_Abstract $report
     * @param array                               $conditions
     *
     * @return array $conditions
     */
    public function getConditions(Mirasvit_Advr_Model_Report_Abstract $report, array $conditions = []);
}
