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



class Mirasvit_Advd_Block_Adminhtml_Widget_Order_Category extends Mirasvit_Advd_Block_Adminhtml_Widget_Abstract_Grid
{
    public function getGroup()
    {
        return 'Sales';
    }

    public function getName()
    {
        return 'Sales by Category';
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
                'label' => Mage::helper('advd')->__('Level'),
                'value' => 'category_level',
                'type' => 'number',
            ),

            array(
                'label' => Mage::helper('advd')->__('Category'),
                'value' => 'category_name',
                'type' => 'text',
                'frame_callback' => array(Mage::helper('advr/callback'), 'category'),
            ),

            array(
                'label' => Mage::helper('advd')->__('Category Path'),
                'value' => 'category_path',
                'type' => 'text',
                'frame_callback' => array(Mage::helper('advr/callback'), 'categoryPath'),
            ),

            array(
                'label' => Mage::helper('advd')->__('Number Of Orders'),
                'value' => 'quantity',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('QTY Ordered'),
                'value' => 'sum_item_qty_ordered',
                'type' => 'number',
            ),

            array(
                'label' => Mage::helper('advd')->__('QTY Refunded'),
                'value' => 'sum_item_qty_refunded',
                'type' => 'number',
            ),

            array(
                'label' => Mage::helper('advd')->__('Tax'),
                'value' => 'sum_item_tax_amount',
                'type' => 'currency',
            ),

            array(
                'label' => Mage::helper('advd')->__('Discount'),
                'value' => 'sum_item_discount_amount',
                'type' => 'currency',
                'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
                'discount_from' => 'sum_item_row_total',
            ),

            array(
                'label' => Mage::helper('advd')->__('Refunded'),
                'value' => 'sum_item_amount_refunded',
                'type' => 'currency',
            ),

            array(
                'label' => Mage::helper('advd')->__('Total'),
                'value' => 'sum_item_row_total',
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
        $nameList[] = 'category_id';

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
            'levels',
            'multiselect',
            array(
                'name' => 'levels',
                'label' => Mage::helper('advr')->__('Category Level'),
                'values' => Mage::getSingleton('advr/system_config_source_category')->getCategoryLevels(),
                'value' => $this->getParam('levels', array()),
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
            ->setBaseTable('catalog/category')
            ->setFilterData($filterData, true, false);

        $this->addCustomerGroupFilter($collection);

        $collection->addFieldToFilter('catalog_category_table.level', array('in' => $this->getParam('levels')));

        $collection->selectColumns($this->getColumnValues())
            ->groupByColumn('category_id');
        $collection->setOrder($this->getParam('sort_by', 'sum_item_qty_ordered'), $this->getParam('sort_dir', 'desc'));

        $grid->setCollection($collection);

        return $this;
    }

    protected function _prepareColumns($grid)
    {
        $baseCurrencyCode = Mage::app()->getStore((int) $this->getParam('store'))->getBaseCurrencyCode();

        $currencyColumns = array('sum_item_tax_amount',
            'sum_item_discount_amount',
            'sum_item_amount_refunded',
            'sum_item_row_total'
        );

        foreach ($this->getParam('columns', array()) as $columnValue) {
            $column = $this->getColumnByValue($columnValue);
            $column['header'] = $column['label'];
            $column['index'] = $column['value'];
            $column['sortable'] = false;
            $column['align'] = 'left';
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
