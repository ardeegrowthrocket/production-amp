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



class Mirasvit_Advd_Block_Adminhtml_Widget_Order_Customergroup extends Mirasvit_Advd_Block_Adminhtml_Widget_Abstract_Grid
{
    public function getGroup()
    {
        return 'Sales';
    }

    public function getName()
    {
        return 'Sales by Customer Group';
    }

    /**
     * Return columns used in grid.
     *
     * @return array
     */
    public function getColumns()
    {
        $columns = array(
            array(
                'label' => Mage::helper('advd')->__('Customer Group'),
                'value' => 'customer_group_code',
                'type' => 'text',
            ),
            array(
                'label' => Mage::helper('advd')->__('Number Of Orders'),
                'value' => 'quantity',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('Items Ordered'),
                'value' => 'sum_total_qty_ordered',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('Discount'),
                'value' => 'sum_discount_amount',
                'type' => 'currency',
                'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
                'discount_from'  => 'sum_subtotal',
            ),
            array(
                'label' => Mage::helper('advd')->__('Shipping'),
                'value' => 'sum_shipping_amount',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Tax'),
                'value' => 'sum_tax_amount',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Shipping Tax'),
                'value' => 'sum_shipping_tax_amount',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Invoiced'),
                'value' => 'sum_total_invoiced',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Refunded'),
                'value' => 'sum_total_refunded',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Subtotal'),
                'value' => 'sum_subtotal',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Grand Total'),
                'value' => 'sum_grand_total',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Invoiced Cost'),
                'value' => 'sum_total_invoiced_cost',
                'type' => 'currency',
            ),
        );

        return $columns;
    }

    /**
     * Return column by column's value.
     *
     * @param $value
     *
     * @return array|null
     */
    public function getColumnByValue($value)
    {
        foreach ($this->getColumns() as $column) {
            if ($column['value'] === $value) {
                return $column;
            }
        }
    }

    /**
     * Return list of column's values.
     *
     * @return array
     */
    public function getColumnValues()
    {
        $nameList = array();
        foreach ($this->getColumns() as $column) {
            $nameList[] = $column['value'];
        }
        $nameList[] = 'customer_group_code';

        return $nameList;
    }


    public function prepareOptions()
    {
        $this->form->addField(
            'interval',
            'select',
            array(
                'name' => 'interval',
                'label' => Mage::helper('advr')->__('Period'),
                'value' => $this->getParam('interval', Mirasvit_Advr_Helper_Date::LAST_24H),
                'values' => Mage::helper('advr/date')->getIntervals(true, true),
            )
        );

        $this->form->addField(
            'columns',
            'multiselect',
            array(
                'name' => 'columns',
                'label' => Mage::helper('advr')->__('Columns'),
                'values' => $this->getColumns(),
                'value' => $this->getParam('columns', array()),
            )
        );

        $this->form->addField(
            'sort_by',
            'select',
            array(
                'name' => 'sort_by',
                'label' => Mage::helper('advr')->__('Sort By Field'),
                'values' => $this->getColumns(),
                'value' => $this->getParam('sort_by', 'sum_item_qty_ordered'),
            )
        );

        $this->form->addField(
            'sort_dir',
            'select',
            array(
                'name' => 'sort_dir',
                'label' => Mage::helper('advr')->__('Sort Direction'),
                'values' => array('asc' => 'ASC', 'desc' => 'DESC'),
                'value' => $this->getParam('sort_dir', 'desc'),
            )
        );

        return $this;
    }
    public function activeFilters()
    {
        return array('customer_groups');
    }

    protected function _prepareCollection($grid)
    {
        $interval = Mage::helper('advr/date')->getInterval($this->getParam('interval'), true);

        $filterData = new Varien_Object(array(
            'from' => $interval->getFrom()->get(Varien_Date::DATETIME_INTERNAL_FORMAT),
            'to' => $interval->getTo()->get(Varien_Date::DATETIME_INTERNAL_FORMAT),
            'store_ids' => $this->getParam('store_ids'),
        ));

        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable('sales/order')
            ->setFilterData($filterData, true, false);

        $this->addCustomerGroupFilter($collection);

        $collection->selectColumns($this->getColumnValues())
            ->groupByColumn('customer_group_code');
        $collection->setOrder($this->getParam('sort_by', 'sum_item_qty_ordered'), $this->getParam('sort_dir', 'desc'));

        $grid->setCollection($collection);

        return $this;
    }

    protected function _prepareColumns($grid)
    {
        $baseCurrencyCode = Mage::app()->getStore((int) $this->getParam('store'))->getBaseCurrencyCode();

        $currencyColumns = array('sum_discount_amount',
            'sum_shipping_amount',
            'sum_tax_amount',
            'sum_shipping_tax_amount',
            'sum_total_invoiced',
            'sum_total_refunded',
            'sum_subtotal',
            'sum_grand_total',
            'sum_total_invoiced_cost'
        );

        foreach ($this->getParam('columns', array()) as $columnValue) {
            $column = $this->getColumnByValue($columnValue);
            $column['header'] = $column['label'];
            $column['index'] = $column['value'];
            $column['sortable'] = false;
            $column['align'] = 'right';
            $column['column_css_class'] = 'nobr';

            if (in_array($column['value'], $currencyColumns)) {
                $column['currency_code'] = $baseCurrencyCode;
            }

            $grid->addColumn($column['value'], $column);
        }

        $grid->setFilterVisibility(false);
        $grid->setPagerVisibility(false);

        return $this;
    }

}
