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



abstract class Mirasvit_Advr_Block_Adminhtml_Order_Abstract extends Mirasvit_Advr_Block_Adminhtml_Block_Container
{
    const KEEP = 'keep';

    protected $incompatibleColumns = array(
        'sum_total_invoiced',
        'avg_total_invoiced',
    );

    protected $creditmemoIncompatibleColumns = array(
        'sum_total_qty_ordered',
        'base_total_refunded',
        'sum_total_refunded',
        'avg_total_qty_ordered',
        'avg_total_refunded',
        'sum_gross_profit',
    );

    /**
     * Get column used in the GROUP BY clause
     *
     * @return string
     */
    abstract protected function getGroupByColumn();

    public function getTotals()
    {
        return $this->getCollection()->getTotals();
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_sales')
            ->increaseGroupConcatMaxLen()
            ->setBaseTable($this->getBaseTable('sales/order'), true)
            ->setRangeFilterTable('sales_' . $this->getSalesSource() . '_table')
            ->setFilterData($this->getFilterData())
            ->selectColumns(array_merge($this->getVisibleColumns(), $this->getAdditionalColumns()))
            ->groupByColumn($this->getGroupByColumn());

        return $collection;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getOrderTableColumns($includePercentOfTotal = false)
    {
        $columns = array();

        if ($includePercentOfTotal) {
            $columns['percent'] = array(
                'header'          => 'Number Of Orders, %',
                'type'            => 'percent',
                'filter'          => false,
                'index'           => 'quantity',
                'frame_callback'  => array(Mage::helper('advr/callback'), 'percent'),
            );
        }

        $columns['quantity'] = array(
            'header' => 'Number Of Orders',
            'type'   => 'number',
        );
        $columns['sum_total_qty_ordered'] = array(
            'header' => 'Items Ordered',
            'type'   => 'number',
        );
        $columns['sum_discount_amount'] = array(
            'header'         => 'Discount',
            'type'           => 'currency',
            'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
            'discount_from'  => 'sum_subtotal',
        );
        $columns['sum_shipping_amount'] = array(
            'header' => 'Shipping',
            'type'   => 'currency',
        );
        $columns['sum_tax_amount'] = array(
            'header' => 'Tax',
            'type'   => 'currency',
        );
        $columns['sum_shipping_tax_amount'] = array(
            'header' => 'Shipping Tax',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['sum_total_invoiced'] = array(
            'header' => 'Invoiced',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['sum_total_refunded'] = array(
            'header' => 'Refunded',
            'type'   => 'currency',
        );
        $columns['sum_subtotal'] = array(
            'header' => 'Subtotal',
            'type'   => 'currency',
        );
        $columns['sum_grand_total'] = array(
            'header' => 'Grand Total',
            'type'   => 'currency',
            'chart'  => true,
        );
        $columns['sum_total_invoiced_cost'] = array(
            'header' => 'Invoiced Cost',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['sum_gross_profit'] = array(
            'header'         => 'Gross Profit',
            'type'           => 'currency',
            'hidden'         => true,
            'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
            'discount_from'  => 'sum_grand_total',
        );
        $columns['avg_total_qty_ordered'] = array(
            'header' => 'Average Items Ordered',
            'type'   => 'number',
            'hidden' => true,
        );
        $columns['avg_discount_amount'] = array(
            'header'         => 'Average Discount',
            'type'           => 'currency',
            'hidden'         => true,
            'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
            'discount_from'  => 'avg_subtotal',
        );
        $columns['avg_subtotal'] = array(
            'header' => 'Average Subtotal',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['avg_shipping_amount'] = array(
            'header' => 'Average Shipping',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['avg_tax_amount'] = array(
            'header' => 'Average Tax',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['avg_shipping_tax_amount'] = array(
            'header' => 'Average Shipping Tax',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['avg_total_invoiced'] = array(
            'header' => 'Average Invoiced',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['avg_total_refunded'] = array(
            'header' => 'Average Refunded',
            'type'   => 'currency',
            'hidden' => true,
        );
        $columns['avg_grand_total'] = array(
            'header' => 'Average Grand Total',
            'type'   => 'currency',
            'hidden' => true,
        );

        if ($this->isRefund()) {
            $columns['sum_orders_qty'] = array(
                'header'   => 'Number Of Orders',
                'type'     => 'number',
                self::KEEP => true,
            );
        }

        return $columns;
    }

    /**
     * Convert order columns to invoice compatible columns.
     *
     * @param array $columns
     *
     * @return array
     */
    protected function convertColumnsToSalesSource(array $columns)
    {
        if ($this->getSalesSource() === Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_ORDER) {
            return $columns;
        }

        // unset columns incompatible with invoice/creditmemo tables
        foreach ($this->incompatibleColumns as $column) {
            unset($columns[$column]);
        }

        if ($this->isRefund()) {
            // unset columns incompatible with creditmemo table
            foreach ($this->creditmemoIncompatibleColumns as $column) {
                unset($columns[$column]);
            }
        }

        // convert order columns to columns of selected sales source
        foreach ($columns as $key => $column) {
            if (isset($column[self::KEEP])) {
                continue;
            }

            $salesSourceKey = $this->getSalesSource() . '_' . $key;

            // replace index
            if (isset($column['index'])) {
                $column['index'] = $this->getSalesSource() . '_' . $column['index'];
            }

            // replace header
            switch ($this->getSalesSource()) {
                case Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_INVOICE:
                    $header = 'Invoice';
                    break;
                case Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_CREDITMEMO:
                    $header = 'Credit Memo';
                    break;
                default:
                    $header = 'Order';
            }

            $column['header'] = str_replace('Order', $header, $column['header']);

            $columns[$salesSourceKey] = $column;

            unset($columns[$key]);
        }

        if ($this->isInvoice()) {
            // adjust separate columns
            $columns['invoice_sum_total_invoiced_cost']['frame_callback'] = array(
                Mage::helper('advr/callback'),
                'invoiceBaseCost'
            );
            $columns['invoice_sum_gross_profit']['frame_callback'] = array(
                Mage::helper('advr/callback'),
                'subtractSumInvoiceBaseCost'
            );
        }

        return $columns;
    }

    /**
     * @return string
     */
    public function getSalesSource()
    {
        return $this->getFilterData()->getSalesSource()
            ? $this->getFilterData()->getSalesSource()
            : Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_ORDER;
    }

    /**
     * Determine whether the source of sales is invoice or not.
     *
     * @return bool
     */
    protected function isInvoice()
    {
        return $this->getSalesSource() === Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_INVOICE;
    }

    /**
     * Determine whether the source of sales is creditmemo or not.
     *
     * @return bool
     */
    protected function isRefund()
    {
        return $this->getSalesSource() === Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_CREDITMEMO;
    }

    protected function getAdditionalColumns()
    {
        $additionalColumns = array('orders');
        if ($this->isInvoice()) {
            $additionalColumns[] = 'invoices';
        }

        return $additionalColumns;
    }

    /**
     * We modify columns' codes based on the sales source (order, invoice...).
     *
     * So this method returns modified column's name, e.g.:
     * $code => period, sales_source => invoice, return value => invoice_period
     * 
     * @param string $code
     *
     * @return string
     */
    protected function getColumn($code)
    {
        foreach ($this->getColumns() as $key => $column) {
            if (strpos($key, $code) !== false) {
                return $key;
            }
        }

        return $code;
    }
}
