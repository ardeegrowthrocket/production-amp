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



class Mirasvit_Advr_Block_Adminhtml_Order_Month extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sales By Months'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setXAxisType($this->getColumn('category'))
            ->setXAxisField($this->getColumn('month_of_year'));

        return $this;
    }

    protected function prepareToolbar()
    {
        $this->initToolbar()
            ->setSalesSourceVisibility(true);

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort($this->getColumn('month_of_year'))
            ->setDefaultDir('asc')
            ->setDefaultLimit(12)
            ->setPagerVisibility(false);

        return $this;
    }

    protected function getGroupByColumn()
    {
        return $this->getColumn('month_of_year');
    }

    public function getColumns()
    {
        $columns = array(
            'month_of_year' => array(
                'header'              => 'Month',
                'type'                => 'text',
                'frame_callback'      => array(Mage::helper('advr/callback'), 'month'),
                'totals_label'        => 'Total',
                'filter_totals_label' => 'Subtotal',
                'filter'              => false,
                self::KEEP            => true
            ),
        );

        $columns += $this->getOrderTableColumns(true);

        $columns = $this->convertColumnsToSalesSource($columns);

        return $columns;
    }
}
