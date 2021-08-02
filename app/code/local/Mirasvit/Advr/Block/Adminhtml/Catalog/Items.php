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



class Mirasvit_Advr_Block_Adminhtml_Catalog_Items extends Mirasvit_Advr_Block_Adminhtml_Catalog_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sold Items'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setResetLimit(false)
            ->setXAxisType('category')
            ->setXAxisField('name');

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort('row_total')
            ->setDefaultDir('desc');

        return $this;
    }

    protected function _prepareCollection()
    {
        Mage::register('ignore_tz', true);

        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable('sales/order_item');

        Mage::getModel('advr/report_table_childOrderItem')->join($collection);
        $this->addColumns($collection);

        $collection->setFilterData($this->getFilterData(), true, false, true, true)
            ->selectColumns(array('product_id', 'item_id'))
            ->selectColumns($this->getVisibleColumns())
            ->groupByColumn('item_id');

        $collection->getSelect()->where('sales_order_item_table.parent_item_id IS NULL');

        //echo '<pre>';
        //print_r($collection->getSelect()->__toString());
        //echo '</pre>';

        return $collection;
    }

    public function getColumns()
    {
        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gteq' => 0))
            ->load()
            ->toOptionHash();

        $columns = array(
            'item_id' => array(
                'header' => Mage::helper('advr')->__('Item ID'),
                'totals_label' => Mage::helper('advr')->__(''),
                'hidden' => true,
                'table' => 'sales/order_item',
                'filter' => false,
                'expression' => 'sales_order_item_table.item_id',
            ),

            'sku' => array(
                'header' => 'SKU',
                'type' => 'array',
                'filter' => false,
                'totals_label' => 'Total',
                'filter_totals_label' => 'Subtotal',
                'link_callback' => array($this, 'rowUrlCallback'),
            ),

            'name' => array(
                'header' => 'Product',
            ),

            'order_id' => array(
                'header' => Mage::helper('advr')->__('Order ID'),
                'totals_label' => Mage::helper('advr')->__(''),
                'hidden' => true,
                'type' => 'array',
                'filter' => false,
            ),

            'invoice_increment_id' => array(
                'header' => Mage::helper('advr')->__('Invoice #'),
                'expression' => 'sales_invoice_table.increment_id',
                'table' => 'sales/invoice',
                'hidden' => true,
                'column_css_class' => 'nobr',
            ),

            'customer_firstname' => array(
                'header' => Mage::helper('advr')->__('Firstname'),
                'column_css_class' => 'nobr',
                'table' => 'sales/order',
            ),

            'customer_lastname' => array(
                'header' => Mage::helper('advr')->__('Lastname'),
                'column_css_class' => 'nobr',
                'table' => 'sales/order',
            ),

            'customer_email' => array(
                'header' => Mage::helper('advr')->__('Email'),
                'column_css_class' => 'nobr',
                'table' => 'sales/order',
            ),

            'customer_group_id' => array(
                'header' => Mage::helper('advr')->__('Customer Group'),
                'type' => 'options',
                'options' => $groups,
                'column_css_class' => 'nobr',
                'table' => 'sales/order',
                'export_callback' => array('Mirasvit_Advr_Block_Adminhtml_Order_Plain', 'frameCallbackCustomerGroup'),
            ),

            'customer_taxvat' => array(
                'header' => Mage::helper('advr')->__('Tax/VAT number'),
                'hidden' => true,
                'table' => 'sales/order',
            ),

            'created_at' => array(
                'header' => Mage::helper('advr')->__('Purchased On'),
                'type' => 'datetime',
                'column_css_class' => 'nobr',
                'export_callback' => array('Mirasvit_Advr_Block_Adminhtml_Order_Plain', 'createdAt'),
                'totals_label' => '',
            ),

            'state' => array(
                'header' => Mage::helper('advr')->__('State'),
                'type' => 'options',
                'options' => Mage::getSingleton('sales/order_config')->getStates(),
                'hidden' => true,
                'table' => 'sales/order',
            ),

            'shipping_address' => array(
                'header'       => Mage::helper('advr')->__('Shipping Address'),
                'hidden'       => true,
                'type'         => 'text',
                'expression'   => 'CONCAT_WS(", ", sales_order_shipping_address_table.street, sales_order_shipping_address_table.city, sales_order_shipping_address_table.region, sales_order_shipping_address_table.postcode, sales_order_shipping_address_table.country_id)',
                'table_method' => 'advr/report_table_shippingAddress',
            ),

            'billing_address' => array(
                'header'      => Mage::helper('advr')->__('Billing Address'),
                'hidden'      => true,
                'type'        => 'text',
                'expression'  => 'CONCAT_WS(", ", sales_order_address_table.street, sales_order_address_table.city, sales_order_address_table.region, sales_order_address_table.postcode, sales_order_address_table.country_id)',
                'table'       => 'sales/order_address',
            ),

            'shipping_city' => array(
                'header'       => Mage::helper('advr')->__('Shipping City'),
                'hidden'       => true,
                'type'         => 'text',
                'expression'   => 'sales_order_shipping_address_table.city',
                'table_method' => 'advr/report_table_shippingAddress',
            ),

            'shipping_telephone' => array(
                'header'       => Mage::helper('advr')->__('Shipping Telephone'),
                'hidden'       => true,
                'type'         => 'text',
                'expression'   => 'sales_order_shipping_address_table.telephone',
                'table_method' => 'advr/report_table_shippingAddress',
            ),

            'order_status' => array(
                'header' => Mage::helper('advr')->__('Status'),
                'type' => 'options',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
            ),
        );

        $columns += $this->getBaseProductColumns();
        $columns += $this->getProductAttributeColumns();

        $columns['actions'] = array(
            'header' => 'Actions',
            'actions' => array(
                array(
                    'caption' => Mage::helper('advr')->__('View Sales'),
                    'callback' => array($this, 'detailUrlCallback'),
                ),
            ),
        );

        unset($columns['percent_ordered'], $columns['quantity'], $columns['quantity_refunded']);

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
            array('id' => $row->getProductId()/*, 'as_child' => $this->getIncludeChild()*/)
        );

        return $url;
    }

    /**
     * @param Mirasvit_Advr_Model_Report_Sales $collection
     *
     * @return Mirasvit_Advr_Model_Report_Sales
     */
    private function addColumns($collection)
    {
        $columns = $this->getColumns();
        $tableDescription = $collection->getConnection()->describeTable($collection->getTable('sales/order_item'));

        // Add every report column to collection
        foreach (array_merge($this->getVisibleColumns(), array('item_id')) as $column) {
            if (isset($columns[$column])) {
                $data = $columns[$column];
                if (isset($tableDescription[$column])) {
                    $data['expression'] = 'sales_order_item_table.' . $column;
                    $data['table'] = 'sales/order_item';
                    if (isset($data['type']) && $data['type'] === 'currency' && strpos($column, 'base') !== false) {
                        $data['expression'] = '(' . $data['expression'] . ')';
                    }

                    $data['label'] = $data['header'];
                    $collection->addColumn($column, $data);
                }
            }
        }

        return $collection;
    }
}
