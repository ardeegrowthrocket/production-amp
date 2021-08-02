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



class Mirasvit_Advr_Block_Adminhtml_Order_Category extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sales By Category'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('pie');

        $this->initChart()
            ->setNameField('category_name')
            ->setValueField('sum_item_row_total');

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort('category_path')
            ->setDefaultDir('asc')
            ->setDefaultLimit(100000)
            ->setPagerVisibility(false);

        return $this;
    }

    protected function getGroupByColumn()
    {
        return 'category_id';
    }

    public function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_sales')
            ->disableOnlyFullGroupByMode()
            ->setBaseTable('catalog/category')
            ->setFilterData($this->getFilterData(), true, false)
            ->selectColumns(array_merge($this->getVisibleColumns(), array('orders')))
            ->selectColumns('category_path')
            ->groupByColumn($this->getGroupByColumn());

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'category_level' => array(
                'header' => 'Level',
                'type' => 'number',
                'sortable' => false,
            ),

            'category_name' => array(
                'header' => 'Category',
                'frame_callback' => array(Mage::helper('advr/callback'), 'category'),
                'chart' => true,
                'sortable' => false,
            ),

            'category_path' => array(
                'header' => 'Category Path',
                'frame_callback' => array(Mage::helper('advr/callback'), 'categoryPath'),
                'sortable' => false,
                'hidden' => true,
            ),
            'category_is_active' => array(
                'header' => 'Is Active',
                'type' => 'options',
                'options' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toArray(),
            ),

            'quantity' => array(
                'header' => 'Number Of Orders',
                'type' => 'number',
                'sortable' => true,
                'chart' => false,
            ),

            'sum_item_qty_ordered' => array(
                'header' => 'QTY Ordered',
                'type' => 'number',
                'sortable' => true,
                'chart' => false,
            ),

            'qty_distinct_products' => array(
                'header' => 'QTY Distinct Products',
                'type' => 'number',
                'sortable' => true,
                'chart' => false,
            ),

            'sum_item_qty_refunded' => array(
                'header' => 'QTY Refunded',
                'type' => 'number',
                'sortable' => true,
                'chart' => false,
            ),
            'sum_item_tax_amount' => array(
                'header' => 'Tax',
                'type' => 'currency',
                'sortable' => true,
                'chart' => false,
            ),
            'sum_item_discount_amount' => array(
                'header' => 'Discount',
                'type' => 'currency',
                'sortable' => true,
                'chart' => false,
                'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
                'discount_from' => 'sum_item_row_total',
            ),
            'sum_item_amount_refunded' => array(
                'header' => 'Refunded',
                'type' => 'currency',
                'sortable' => true,
                'chart' => false,
            ),
            'sum_item_row_total' => array(
                'header' => 'Total',
                'type' => 'currency',
                'sortable' => true,
                'chart' => true,
            ),
        );

        $columns['actions'] = array(
            'header' => 'Actions',
            'renderer' => 'Mirasvit_Advr_Block_Adminhtml_Block_Grid_Renderer_PostAction',
        );

        return $columns;
    }

    public function getTotals()
    {
        return false;
    }
}
