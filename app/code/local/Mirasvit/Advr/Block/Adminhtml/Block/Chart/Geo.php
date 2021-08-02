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



class Mirasvit_Advr_Block_Adminhtml_Block_Chart_Geo extends Mirasvit_Advr_Block_Adminhtml_Block_Chart_Abstract
{
    public function _prepareLayout()
    {
        $this->setTemplate('mst_advr/block/chart/geo.phtml');

        return parent::_prepareLayout();
    }

    public function getSeries()
    {
        $series = array();

        foreach ($this->getCollection() as $itm) {
            $row = array();
            $hasAll = true;
            foreach ($this->columns as $column) {
                $value = $itm->getData($column->getField());

                if ($value == '') {
                    $hasAll = false;
                }

                $row[] = $this->_castValue($column, $value);
            }

            if ($hasAll) {
                $series[] = $row;
            }
        }

        return $series;
    }
}
