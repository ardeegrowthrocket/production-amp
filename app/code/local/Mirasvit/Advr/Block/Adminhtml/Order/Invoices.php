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



/**
 * Class Mirasvit_Advr_Block_Adminhtml_Order_InvoicedPlain.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class Mirasvit_Advr_Block_Adminhtml_Order_Invoices extends Mirasvit_Advr_Block_Adminhtml_Block_Container
{
    public function getTotals()
    {
        return $this->getUniqueTotals($this->getCollection(), 'entity_id');
    }

    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Invoices'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setXAxisType('invoice')
            ->setXAxisField('increment_id');

        return $this;
    }

    public function getFilterData()
    {
        $filterData = parent::getFilterData();

        if (isset($filterData['invoices'])) {
            $filterData['entity_id'] = explode(',', $filterData['invoices']);
        }

        unset($filterData['orders']);

        return $filterData;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort('sales_invoice_table.base_grand_total')
            ->setDefaultDir('desc')
            ->setPagerVisibility(true)
            ->setRowUrlCallback(array($this, 'rowUrlCallback'));

        return $this;
    }

    public function _prepareCollection()
    {
        $columns = $this->getColumns();
        $filterData = clone $this->getFilterData();
        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable('sales/invoice')
            ->setRangeFilterTable('sales_invoice_table');

        $tableDescription = $collection->getConnection()->describeTable($collection->getTable('sales/invoice'));

        // Add every report column to collection
        foreach (array_merge($this->getVisibleColumns(),array('entity_id')) as $column) {
            $data = $columns[$column];
            if (isset($tableDescription[$column])) {
                $data['expression'] = 'sales_invoice_table.'.$column;
                $data['table'] = 'sales/invoice';
                if(isset($data['type']) &&
                    $data['type'] == 'currency' &&
                    strpos($column, 'base') !== false) {
                    $data['expression'] = '(' . $data['expression'] . ')';
                }
            }
            $data['label'] = $data['header'];
            $collection->addColumn($column, $data);
        }

        $collection->setFilterData($filterData->unsInvoices(), false, true) // Unset extra data from filters
            ->selectColumns(array_merge($this->getVisibleColumns(),array('entity_id')));

        $collection->getSelect()->group('sales_invoice_table.entity_id');

        $this->applyFilter($collection);

        $this->setCollection($collection);

        return $collection;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getColumns()
    {
        $paymentMethodOptions = Mage::getSingleton('advr/system_config_source_paymentMethod')->toOptionHash();
        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gteq' => 0))
            ->load()
            ->toOptionHash();

        $columns = array(
            'increment_id' => array(
                'header' => Mage::helper('advr')->__('Invoice #'),
                'expression' => 'sales_invoice_table.increment_id',
                'totals_label' => Mage::helper('advr')->__('Totals'),
            ),

            'entity_id' => array(
                'header' => Mage::helper('advr')->__('Invoice ID'),
                'totals_label' => Mage::helper('advr')->__(''),
                'hidden' => true,
                'table' => 'sales/invoice',
                'type' => 'array',
                'filter' => false,
                'expression' => 'sales_invoice_table.entity_id',
            ),

            'order_increment_id' => array(
                'header' => Mage::helper('advr')->__('Order #'),
                'expression' => 'sales_order_table.increment_id',
                'table' => 'sales/order',
                'hidden' => true,
                'column_css_class' => 'nobr',
            ),

            'customer_firstname' => array(
                'header' => Mage::helper('advr')->__('Firstname'),
                'column_css_class' => 'nobr',
                'expression' => 'sales_order_table.customer_firstname',
                'table' => 'sales/order',
            ),

            'customer_lastname' => array(
                'header' => Mage::helper('advr')->__('Lastname'),
                'column_css_class' => 'nobr',
                'expression' => 'sales_order_table.customer_lastname',
                'table' => 'sales/order',
            ),

            'customer_email' => array(
                'header' => Mage::helper('advr')->__('Email'),
                'column_css_class' => 'nobr',
                'expression' => 'sales_order_table.customer_email',
                'table' => 'sales/order',
            ),

            'customer_group_id' => array(
                'header' => Mage::helper('advr')->__('Customer Group'),
                'type' => 'options',
                'options' => $groups,
                'column_css_class' => 'nobr',
                'export_callback' => array($this, 'frameCallbackCustomerGroup'),
                'expression' => 'sales_order_table.customer_group_id',
                'table' => 'sales/order',
            ),

            'customer_taxvat' => array(
                'header' => Mage::helper('advr')->__('Tax/VAT number'),
                'hidden' => true,
                'expression' => 'sales_order_table.customer_taxvat',
                'table' => 'sales/order',
            ),

            'created_at' => array(
                'header' => Mage::helper('advr')->__('Invoiced Date'),
                'type' => 'datetime',
                'column_css_class' => 'nobr',
                'export_callback' => array($this, 'createdAt'),
                'totals_label' => '',
            ),

            'state' => array(
                'header' => Mage::helper('advr')->__('Invoice State'),
                'type' => 'options',
                'options' => Mage::getSingleton('sales/order_invoice')->getStates(),
                'hidden' => true,
                'expression' => 'sales_invoice_table.state',
                'table' => 'sales/invoice',
            ),

            'status' => array(
                'header' => Mage::helper('advr')->__('Order Status'),
                'type' => 'options',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
                'expression' => 'sales_order_table.status',
                'table' => 'sales/order',
            ),

            'products' => array(
                'header' => Mage::helper('advr')->__('Item(s)'),
                'sortable' => false,
                'filter' => false,
                'expression' => 'sales_invoice_table.entity_id',
                'frame_callback' => array($this, 'products'),
                'hidden' => true,
            ),

            'payment_method' => array(
                'type' => 'options',
                'header' => Mage::helper('advr')->__('Payment Type'),
                'hidden' => true,
                'options' => $paymentMethodOptions,
                'expression' => 'sales_order_payment_table.method',
                'table' => 'sales/order_payment',
            ),

            'total_qty' => array(
                'header' => Mage::helper('advr')->__('Total QTY'),
                'type' => 'number',
            ),

            'base_tax_amount' => array(
                'header' => Mage::helper('advr')->__('Tax'),
                'type' => 'currency',
                'hidden' => true,
            ),

            'base_shipping_amount' => array(
                'header' => Mage::helper('advr')->__('Shipping'),
                'type' => 'currency',
                'hidden' => true,
            ),

            'base_discount_amount' => array(
                'header' => Mage::helper('advr')->__('Discount'),
                'type' => 'currency',
            ),

            'base_total_refunded' => array(
                'header' => Mage::helper('advr')->__('Refunded'),
                'type' => 'currency',
            ),

            'base_total_paid' => array(
                'header' => Mage::helper('advr')->__('Paid'),
                'type' => 'currency',
                'hidden' => true,
            ),

            'base_grand_total' => array(
                'header' => Mage::helper('advr')->__('Total Invoiced'),
                'type' => 'currency',
                'chart' => true,
            ),

            'gross_profit' => array(
                'header' => Mage::helper('advr')->__('Gross Profit'),
                'type' => 'currency',
                'frame_callback' => array(Mage::helper('advr/callback'), 'subtractInvoiceBaseCost'),
                'expression' => 'sales_invoice_table.base_grand_total',
                'table' => 'sales_invoice_table',
                'chart' => false,
            ),
        );

        $columns['actions'] = array(
            'header' => 'Actions',
            'hidden' => true,
            'actions' => array(
                array(
                    'caption' => Mage::helper('advr')->__('View'),
                    'callback' => array($this, 'rowUrlCallback'),
                ),
            ),
        );

        return $columns;
    }

    public function createdAt($value, $row, $column)
    {
        $data = Mage::app()->getLocale()
            ->date($row->getCreatedAt(), Varien_Date::DATETIME_INTERNAL_FORMAT)->toString();
        return $data;
    }

    private function applyFilter($collection)
    {
        $filterData = $this->getFilterData();
        if (isset($filterData['invoices'])) {
            $collection->addFieldToFilter('sales_invoice_table.entity_id', array(
                'in' => explode(',', $filterData['invoices'])
            ));
        }
    }

    public function rowUrlCallback($row)
    {
        return $this->getUrl('adminhtml/sales_invoice/view', array('invoice_id' => $row->getEntityId()));
    }

    public function products($value, $row, $column)
    {
        $data = array();
        $row = Mage::getModel('sales/order_invoice')->load($row->getEntityId());
        $items = $row->getAllItems();
        foreach ($items as $item) {
            $url = $this->getUrl('adminhtml/catalog_product/edit', array('id' => $item->getProductId()));
            $data[] = '<a class="nobr" target="_blank" href="'.$url.'">'
                .$item->getSku()
                .' / '
                .Mage::helper('core/string')->truncate($item->getName(), 50)
                .' / '.intval($item->getQty())
                .' × '.Mage::helper('core')->currency($item->getBasePrice())
                .'</a>';
        }

        return implode('<br>', $data);
    }

    public function getFilterColumns()
    {
        // Restrict columns available only for this report
        return array_intersect_key($this->getCollection()->getColumns(), $this->getColumns());
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function frameCallbackCustomerGroup($value, $row, $column)
    {
        return $value;
        //return Mage::getModel('customer/group')->load((int)$value)->getCode();
    }
}
