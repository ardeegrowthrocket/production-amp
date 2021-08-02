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



class Mirasvit_Advr_Block_Adminhtml_Catalog_Lowstock extends Mirasvit_Advr_Block_Adminhtml_Catalog_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Low stock'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setXAxisType('category')
            ->setXAxisField('product_default_name');

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort('product_stock_qty')
            ->setDefaultDir('asc');

        return $this;
    }

    protected function prepareToolbar()
    {
        $this->initToolbar();

        $this->getToolbar()->getForm()->addField('include_child', 'checkbox', array(
            'name' => 'include_child',
            'label' => Mage::helper('advr')->__('Include child products'),
            'value' => 1,
            'checked' => $this->getIncludeChild(),
        ));

        $this->getToolbar()->getForm()->addField('show_all_products', 'checkbox', array(
            'name' => 'show_all_products',
            'label' => Mage::helper('advr')->__('Show All Products'),
            'value' => 1,
            'checked' => $this->getShowAllProducts(),
        ));


        return $this;
    }

    protected function unsFilterParameters()
    {
        if ($this->getShowAllProducts()) {
            $this->getFilterData()->unsFrom();
            $this->getFilterData()->unsTo();
            // We get all the unsold products and the products in the order with filtering statuses
        }
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_sales')
            ->disableOnlyFullGroupByMode()
            ->setBaseTable('catalog/product');
        // to get all products we need to disable orders filters.
        $this->unsFilterParameters();

        $accountingConfigProcessOrder = $this->getShowAllProducts() ? false : true;

        $collection->setFilterData($this->getFilterData(), $accountingConfigProcessOrder, false)
            ->selectColumns('product_id')
            ->selectColumns($this->getVisibleColumns())
            ->groupByColumn('product_id');

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'product_sku' => array(
                'header' => 'SKU',
                'type' => 'text',
                'totals_label' => 'Total',
                'filter_totals_label' => 'Subtotal',
                'link_callback' => array($this, 'rowUrlCallback'),
            ),

            'product_default_name' => array(
                'header' => 'Product',
            ),

            'product_stock_qty' => array(
                'header' => 'Stock Quantity',
                'type' => 'number',
                'chart' => true,
            ),

            'product_is_in_stock' => array(
                'header' => 'Stock Availability',
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_stock')->toOptionHash(),
            ),
        );

        $columns += $this->getBaseProductColumns(true);
        if (isset($columns['sum_item_row_total'])) {
            $columns['sum_item_row_total']['chart'] = false;
        }

        $columns['actions'] = array(
            'header' => 'Actions',
            'actions' => array(
                array(
                    'caption' => Mage::helper('advr')->__('View Product'),
                    'callback' => array($this, 'rowUrlCallback'),
                ),
            ),
        );

        return $columns;
    }

    public function rowUrlCallback($row)
    {
        return $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getData('product_id')));
    }

    public function getIncludeChild()
    {
        if (!$this->getFilterData()->getIncludeChild()) {
            return 0;
        }

        return $this->getFilterData()->getIncludeChild();
    }

    public function getShowAllProducts()
    {
        if (!$this->getFilterData()->getShowAllProducts()) {
            return 0;
        }

        return $this->getFilterData()->getShowAllProducts();
    }
}
