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



class Mirasvit_Advr_Block_Adminhtml_Catalog_Product extends Mirasvit_Advr_Block_Adminhtml_Catalog_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Products / Bestsellers'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setXAxisType('category')
            ->setXAxisField('product_name');

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort('sum_item_row_total')
            ->setDefaultDir('desc');

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

        return $this;
    }

    protected function _prepareCollection()
    {
        Mage::register('ignore_tz', true, true);
        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable('catalog/product')
            ->setFilterData($this->getFilterData(), true, false, true, true)
            ->selectColumns('product_id');

        Mage::getModel('advr/report_table_view')->join($collection, $this->getFilterData()->toArray());
        $collection->selectColumns($this->getVisibleColumns())
            ->groupByColumn('product_id');

        //$collection->getSelect()->ignoreIndex(array('product_id,parent_item_id'=> 'sales_order_item_table'));

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'product_sku' => array(
                'header' => 'SKU',
                'type' => 'array',
                'filter' => false,
                'totals_label' => 'Total',
                'filter_totals_label' => 'Subtotal',
                'link_callback' => array($this, 'rowUrlCallback'),
            ),

            'product_name' => array(
                'header' => 'Product',
            ),
            'qtyVisited' => array(
                'header' => Mage::helper('advr')->__('Unique Guest Visits'),
                'type' => 'number',
                'totals_label' => '',
            ),

            'qtyCustomerVisited' => array(
                'header' => Mage::helper('advr')->__('Unique Customer Visits'),
                'type' => 'number',
                'totals_label' => '',
            ),

            'cr' => array(
                'header' => Mage::helper('advr')->__('Conversion Rate, %'),
                'type' => 'number',
                'totals_label' => '',
            ),
        );

        $columns += $this->getBaseProductColumns(true);
        $columns += $this->getProductAttributeColumns();

        $columns['actions'] = array(
            'header' => 'Actions',
            'actions' => array(
                array(
                    'caption' => Mage::helper('advr')->__('Detail'),
                    'callback' => array($this, 'detailUrlCallback'),
                ),
            ),
        );

        return $columns;
    }

    public function rowUrlCallback($row)
    {
        return $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getProductId()));
    }

    public function detailUrlCallback($row)
    {
        $url = $this->getUrl(
            'adminhtml/advr_catalog/productDetail',
            array('id' => $row->getProductId(), 'as_child' => $this->getIncludeChild())
        );

        return $url;
    }

    public function getIncludeChild()
    {
        if (!$this->getFilterData()->getIncludeChild()) {
            return 0;
        }

        return $this->getFilterData()->getIncludeChild();
    }
}
