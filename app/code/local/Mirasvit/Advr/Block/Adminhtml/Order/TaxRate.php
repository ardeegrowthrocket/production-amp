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



class Mirasvit_Advr_Block_Adminhtml_Order_TaxRate extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sales By Tax Rates'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setXAxisType($this->getColumn('category'))
            ->setXAxisField($this->getColumn('taxrate_tax_code'));

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
            ->setDefaultSort($this->getColumn('taxrate_tax_code'));

        return $this;
    }

    protected function getGroupByColumn()
    {
        return $this->getColumn('taxrate_tax_code');
    }

    public function getColumns()
    {
        $columns = array(
            'taxrate_tax_code' => array(
                'header' => 'Tax Identifier',
                'type' => 'text',
                self::KEEP => true
            ),
            'taxrate_tax_title' => array(
                'header' => 'Tax Title',
                'type' => 'text',
                'hidden' => true,
                self::KEEP => true
            ),
            'taxrate_tax_percent' => array(
                'header' => 'Tax Rate',
                'type' => 'number',
                'chart' => true,
                'totals_label' => '',
                self::KEEP => true
            ),/*
            'quantity' => array(
                'header' => 'Number Of Orders',
                'type'   => 'number',
            ),
            'sum_item_qty_ordered' => array(
                'header' => 'Items Ordered',
                'type' => 'number',
            ),
            'sum_item_qty_refunded' => array(
                'header' => 'Items Refunded',
                'type' => 'number',
            ),
            'sum_item_amount_refunded' => array(
                'header' => 'Refunded',
                'type' => 'currency',
            ),
            'sum_item_tax_amount' => array(
                'header' => 'Tax',
                'type' => 'currency',
            ),
            'sum_item_discount_amount' => array(
                'header' => 'Discount',
                'type' => 'currency',
            ),
            'sum_item_row_invoiced' => array(
                'header' => 'Invoiced',
                'type' => 'currency',
            ),
            'sum_item_row_total' => array(
                'header' => 'Total',
                'type' => 'currency',
            ),*/
        );

        $columns += $this->getOrderTableColumns(false);

        $columns = $this->convertColumnsToSalesSource($columns);

        $columns['actions'] = array(
            'header' => 'Actions',
            'renderer' => 'Mirasvit_Advr_Block_Adminhtml_Block_Grid_Renderer_PostAction',
        );

        return $columns;
    }

    public function rowUrlCallback($row)
    {
        $row->setRange($this->getFilterData()->getRange());

        return Mage::helper('advr/callback')->rowUrl('*/*/plain', $row, array('orders', $this->getColumn('period')));
    }
}
