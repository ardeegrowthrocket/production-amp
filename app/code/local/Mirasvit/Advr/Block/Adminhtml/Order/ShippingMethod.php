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



class Mirasvit_Advr_Block_Adminhtml_Order_ShippingMethod extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sales By Shipping Method'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('pie');

        $this->initChart()
            ->setNameField($this->getColumn('shipping_method'))
            ->setValueField($this->getColumn('sum_grand_total'));

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
            ->setDefaultSort($this->getColumn('sum_grand_total'))
            ->setDefaultDir('desc')
            ->setDefaultLimit(100)
            ->setPagerVisibility(false);

        return $this;
    }

    protected function getGroupByColumn()
    {
        return $this->getColumn('shipping_method');
    }

    public function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable($this->getBaseTable('sales/order'))
            ->setFilterData($this->getFilterData(), true, false)
            ->selectColumns($this->getVisibleColumns())
            ->groupByColumn($this->getGroupByColumn());

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'shipping_method' => array(
                'header'              => 'Shipping Method',
                'type'                => 'text',
                'frame_callback'      => array(Mage::helper('advr/callback'), 'shippingMethod'),
                'totals_label'        => 'Total',
                'filter_totals_label' => 'Subtotal',
                'filter'              => false,
                self::KEEP => true
            ),
        );

        $columns += $this->getOrderTableColumns(true);

        $columns = $this->convertColumnsToSalesSource($columns);

        return $columns;
    }
}
