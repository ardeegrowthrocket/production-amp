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



class Mirasvit_Advd_Block_Adminhtml_Widget_Catalog_Lowstock extends Mirasvit_Advd_Block_Adminhtml_Widget_Abstract_Grid
{
    public function getGroup()
    {
        return 'Catalog';
    }

    public function getName()
    {
        return 'Low stock';
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
                'label' => Mage::helper('advd')->__('SKU'),
                'value' => 'product_sku',
                'type' => 'text',
            ),
            array(
                'label' => Mage::helper('advd')->__('Name'),
                'value' => 'product_default_name',
                'type' => 'text',
            ),
            array(
                'label' => Mage::helper('advd')->__('Stock Quantity'),
                'value' => 'product_stock_qty',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('Stock Availability'),
                'value' => 'product_is_in_stock',
                'type' => 'text',
            ),/*
            array(
                'label' => Mage::helper('advd')->__('Qty ordered'),
                'value' => 'sum_item_qty_ordered',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('Qty refunded'),
                'value' => 'sum_item_qty_refunded',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('Total'),
                'value' => 'sum_item_row_total',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Price'),
                'value' => 'avg_item_base_price',
                'type' => 'currency',
            ),
            array(
                'label' => Mage::helper('advd')->__('Gross Profit'),
                'value' => 'item_gross_profit',
                'type' => 'currency',
            ),*/
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
        $nameList[] = 'product_id';

        return $nameList;
    }

    public function prepareOptions()
    {
        $this->form->addField(
            'limit',
            'text',
            array(
                'name' => 'limit',
                'label' => Mage::helper('advr')->__('Number products'),
                'value' => $this->getParam('limit', 5),
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
                'value' => $this->getParam('sort_by', 'product_stock_qty'),
            )
        );

        $this->form->addField(
            'sort_dir',
            'select',
            array(
                'name' => 'sort_dir',
                'label' => Mage::helper('advr')->__('Sort Direction'),
                'values' => array('asc' => 'ASC', 'desc' => 'DESC'),
                'value' => $this->getParam('sort_dir', 'asc'),
            )
        );

        return $this;
    }

    protected function _prepareCollection($grid)
    {
        $filterData = new Varien_Object(array(
            'store_ids' => $this->getParam('store_ids'),
        ));

        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable('catalog/product')
            ->setFilterData($filterData, false, false, false)
            ->selectColumns($this->getColumnValues())
            ->groupByColumn('product_id');
        $collection->setOrder($this->getParam('sort_by', 'product_stock_qty'), $this->getParam('sort_dir', 'asc'));

        $grid->setCollection($collection);

        return $this;
    }

    protected function _prepareColumns($grid)
    {
        $baseCurrencyCode = Mage::app()->getStore((int) $this->getParam('store'))->getBaseCurrencyCode();

        foreach ($this->getParam('columns', array()) as $columnValue) {
            $column = $this->getColumnByValue($columnValue);
            $column['header'] = $column['label'];
            $column['index'] = $column['value'];
            $column['sortable'] = false;
            $column['align'] = 'right';
            $column['column_css_class'] = 'nobr';

            if ('Stock Availability' === $column['label']) {
                $column['type'] = 'options';
                $column['options'] = Mage::getSingleton('advr/system_config_source_stock')->toOptionHash();
            }

            if (in_array($column['value'], array('sum_item_row_total', 'avg_item_base_price', 'item_gross_profit'))) {
                $column['currency_code'] = $baseCurrencyCode;
            }

            $grid->addColumn($column['value'], $column);
        }

        $grid->setFilterVisibility(false);
        $grid->setPagerVisibility(false);
        $grid->setDefaultLimit($this->getParam('limit', 5));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getProductId()));
    }
}
