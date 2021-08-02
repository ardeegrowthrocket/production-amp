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



class Mirasvit_Advd_Helper_Validator extends Mirasvit_MstCore_Helper_Validator_Abstract
{
    public function testEnhancedGrids()
    {
        $result = self::SUCCESS;
        $title = 'Advanced Dashboard: ENHANCED ADMIN GRIDS';
        $description = array();

        if ($this->isModuleEnabled('BL_CustomGrid')) {
            $exclusionExists = false;

            $exclusionList = Mage::helper('customgrid/config')->getExclusionsList();
            foreach ($exclusionList as $exclusion) {
                if (isset($exclusion['block_type']) && strpos($exclusion['block_type'], 'advd') !== false) {
                    $exclusionExists = true;
                }
            }

            $grids = Mage::getModel('customgrid/grid')->getCollection()
                ->addFieldToFilter('block_type', array('like' => 'advd/%'));
            $gridsExist = $grids->getSize();

            if (!$exclusionExists || $gridsExist) {
                $result = self::WARNING;
                $description[] = 'You use the extension "ENHANCED ADMIN GRIDS", due to using a large amount of resources this extension can slow down the dashboard widgets provided by our extension.';
                $description[] = 'In order to solve this problem follow below steps:';

                if (!$exclusionExists) {
                    $description[] = ' - Add global exclusion for our extension:  Block Type: "<b>advd*</b>", Rewriting Class: "<b>*</b>", at section "Base Configuration" of the extension "ENHANCED ADMIN GRIDS".';
                }

                if ($gridsExist) {
                    $description[] = ' - Remove all records for block types of our extension. For this search for "advd" in the field "Block Type" at the section "System > Custom Grids > List"';
                }
            }
        }

        return array($result, $title, $description);
    }
}
