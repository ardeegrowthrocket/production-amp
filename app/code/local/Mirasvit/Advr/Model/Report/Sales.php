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



class Mirasvit_Advr_Model_Report_Sales extends Mirasvit_Advr_Model_Report_Abstract
{
    private $relationsBySalesSource = [
        'invoice' => array(
            array(
                'sales/invoice',
                'sales/order_item',
                'sales_invoice_table.order_id = sales_order_item_table.order_id',
            ),
            array(
                'sales/invoice',
                'sales/order_address',
                'sales_invoice_table.billing_address_id = sales_order_address_table.entity_id',
            ),
            array(
                'sales/invoice',
                'sales/order_payment',
                'sales_invoice_table.order_id = sales_order_payment_table.parent_id',
            ),
            array(
                'sales/invoice',
                'sales/order_tax',
                'sales_invoice_table.order_id = sales_order_tax_table.order_id',
            ),
        ),
        'creditmemo' => array(
            array(
                'sales/creditmemo',
                'sales/order_item',
                'sales_creditmemo_table.order_id = sales_order_item_table.order_id',
            ),
            array(
                'sales/creditmemo',
                'sales/order_address',
                'sales_creditmemo_table.billing_address_id = sales_order_address_table.entity_id',
            ),
            array(
                'sales/creditmemo',
                'sales/order_payment',
                'sales_creditmemo_table.order_id = sales_order_payment_table.parent_id',
            ),
            array(
                'sales/creditmemo',
                'sales/order_tax',
                'sales_creditmemo_table.order_id = sales_order_tax_table.order_id',
            ),
        ),
    ];

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _construct()
    {
        $this->_init('sales/order');

        $isRegisteredCustomer = 'sales_order_table.customer_id IS NOT NULL';

        $isNewCustomer = 'DATE_FORMAT(sales_order_table.created_at, "%Y-%m-%d")
            = DATE_FORMAT(customer_entity_table.created_at, "%Y-%m-%d") OR customer_entity_table.created_at IS NULL';

        $shippingTime = 'unix_timestamp(sales_shipment_table.created_at)
            - unix_timestamp(sales_order_table.created_at)';

        $this->relations = array(
            array(
                'sales/order',
                'sales/order_item',
                'sales_order_table.entity_id = sales_order_item_table.order_id',
            ),
            array(
                'sales/order',
                'customer/customer_group',
                'sales_order_table.customer_group_id = customer_customer_group_table.customer_group_id',
            ),
            array(
                'sales/order',
                'customer/entity',
                'sales_order_table.customer_id = customer_entity_table.entity_id',
            ),
            array(
                'customer/entity',
                'log/customer',
                'customer_entity_table.entity_id = log_customer_table.customer_id',
            ),
            array(
                'sales/order',
                'sales/order_address',
                'sales_order_table.billing_address_id = sales_order_address_table.entity_id',
            ),
            array(
                'sales/order',
                'sales/order_payment',
                'sales_order_table.entity_id = sales_order_payment_table.parent_id',
            ),
            array(
                'advr/postcode',
                'sales/order_address',
                array(
                    'advr_postcode_table.postcode
                        = REPLACE(REPLACE(sales_order_address_table.postcode, " ", ""), "-","")',
                    'advr_postcode_table.country_id = sales_order_address_table.country_id',
                ),
            ),
            array(
                'sales/order_item',
                'catalog/product',
                array('sales_order_item_table.product_id = catalog_product_table.entity_id'),
                'advr/report_condition_orderItem',
            ),
            array(
                'sales/order_item',
                'catalog/category_product',
                'sales_order_item_table.product_id = catalog_category_product_table.product_id',
            ),
            array(
                'catalog/category',
                'catalog/category_product',
                'catalog_category_product_table.category_id = catalog_category_table.entity_id',
            ),
            array(
                'sales/order',
                'sales/shipment',
                'sales_order_table.entity_id = sales_shipment_table.order_id',
            ),
            array(
                'catalog/product',
                'cataloginventory/stock_item',
                'catalog_product_table.entity_id = cataloginventory_stock_item_table.product_id',
            ),
            array(
                'sales/order',
                'sales/order_status',
                'sales_order_table.status = sales_order_status_table.status',
            ),
            array(
                'sales/order',
                'sales/invoice',
                'sales_order_table.entity_id = sales_invoice_table.order_id',
            ),
            array(
                'sales/order',
                'sales/creditmemo',
                'sales_order_table.entity_id = sales_creditmemo_table.order_id',
            ),
            array(
                'sales/order',
                'salesrule/rule',
                'FIND_IN_SET(salesrule_rule_table.rule_id, sales_order_table.applied_rule_ids)',
            ),/*
            array(
                'sales/order_item',
                'tax/sales_order_tax_item',
                'tax_sales_order_tax_item_table.item_id = sales_order_item_table.item_id',
            ),
            array(
                'tax/sales_order_tax_item',
                'sales/order_tax',
                'tax_sales_order_tax_item_table.tax_id = sales_order_tax_table.tax_id',
            ),*/
            array(
                'sales/order',
                'sales/order_tax',
                'sales_order_table.entity_id = sales_order_tax_table.order_id',
            ),
        );

        $this->addColumn(
            'order_status',
            array(
                'label' => 'Status',
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_status')->toOptionHash(),
                'expression' => 'sales_order_table.status',
                'table' => 'sales/order',
            )
        )->addColumn(
            'order_status_label',
            array(
                'label' => 'Status Label',
                'type' => 'options',
                'options' => Mage::getResourceModel('sales/order_status_collection')->toOptionHash(),
                'expression' => 'sales_order_status_table.label',
                'table' => 'sales/order_status',
            )
        )->addColumn(
            'quantity',
            array(
                'label' => 'Number of orders',
                'type' => 'number',
                'expression' => 'COUNT(DISTINCT(sales_order_table.entity_id))',
                'table' => 'sales/order',
            )
        )->addColumn(
            'quantity_refunded',
            array(
                'label' => 'Number of refunded orders',
                'type' => 'number',
                'expression' => 'SUM(IF(sales_order_table.base_total_refunded > 0, 1, 0))',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_total_qty_ordered',
            array(
                'label' => 'Total Qty Ordered',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.total_qty_ordered)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_discount_amount',
            array(
                'label' => 'Discount Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_discount_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_shipping_amount',
            array(
                'label' => 'Shipping Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_shipping_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_tax_amount',
            array(
                'label' => 'Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_tax_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_total_refunded',
            array(
                'label' => 'Total Refunded',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_total_refunded)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_shipping_tax_amount',
            array(
                'label' => 'Shipping Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_shipping_tax_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_total_invoiced',
            array(
                'label' => 'Total Invoiced',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_total_invoiced)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_total_invoiced_cost',
            array(
                'label' => 'Total Invoiced Cost',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_total_invoiced_cost)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_subtotal',
            array(
                'label' => 'Subtotal',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_subtotal)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_subtotal',
            array(
                'label' => 'Avg Subtotal',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_subtotal)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_grand_total',
            array(
                'label' => 'Grand Total',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_grand_total)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'sum_gross_profit',
            array(
                'label' => 'Gross Profit',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_table.base_subtotal_invoiced - sales_order_table.base_total_invoiced_cost)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_total_qty_ordered',
            array(
                'label' => 'Avg Total Qty Ordered',
                'type' => 'currency',
                'expression' => 'ROUND(AVG(sales_order_table.total_qty_ordered), 2)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_discount_amount',
            array(
                'label' => 'Avg Discount Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_discount_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_shipping_amount',
            array(
                'label' => 'Avg Shipping Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_shipping_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_tax_amount',
            array(
                'label' => 'Avg Tax Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_tax_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_total_refunded',
            array(
                'label' => 'Avg Total Refunded',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_total_refunded)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_shipping_tax_amount',
            array(
                'label' => 'Avg Shipping Tax Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_shipping_tax_amount)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_total_invoiced',
            array(
                'label' => 'Avg Total Invoiced',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_total_invoiced)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_total_invoiced_cost',
            array(
                'label' => 'Avg Total Invoiced Cost',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_total_invoiced_cost)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_gross_profit',
            array(
                'label' => 'Avg Gross Profit',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_subtotal_invoiced - order_table.base_total_invoiced_cost)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'avg_grand_total',
            array(
                'label' => 'Avg Grand Total',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_table.base_grand_total)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'invoice_quantity',
            array(
                'label' => 'Number of invoices',
                'type' => 'number',
                'expression' => 'COUNT(DISTINCT(sales_invoice_table.entity_id))',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_total_qty_ordered',
            array(
                'label' => 'Total Qty Invoiced',
                'type' => 'number',
                'expression' => 'SUM(sales_invoice_table.total_qty)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_discount_amount',
            array(
                'label' => 'Invoice Discount Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_discount_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_shipping_amount',
            array(
                'label' => 'Invoice Shipping Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_shipping_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_tax_amount',
            array(
                'label' => 'Invoice Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_tax_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_total_refunded',
            array(
                'label' => 'Invoice Total Refunded',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_total_refunded)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_shipping_tax_amount',
            array(
                'label' => 'Invoice Shipping Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_shipping_tax_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_total_invoiced_cost',
            array(
                'label' => 'Total Invoiced Cost',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_grand_total)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_subtotal',
            array(
                'label' => 'Invoice Subtotal',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_subtotal)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_grand_total',
            array(
                'label' => 'Invoice Grand Total',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_grand_total)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_sum_gross_profit',
            array(
                'label' => 'Invoice Gross Profit',
                'type' => 'currency',
                'expression' => 'SUM(sales_invoice_table.base_subtotal)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_total_qty_ordered',
            array(
                'label' => 'Avg Total Qty Invoiced',
                'type' => 'currency',
                'expression' => 'ROUND(AVG(sales_invoice_table.total_qty), 2)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_discount_amount',
            array(
                'label' => 'Invoice Avg Discount Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_discount_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_shipping_amount',
            array(
                'label' => 'Invoice Avg Shipping Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_shipping_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_tax_amount',
            array(
                'label' => 'Invoice Avg Tax Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_tax_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_total_refunded',
            array(
                'label' => 'Invoice Avg Total Refunded',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_total_refunded)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_shipping_tax_amount',
            array(
                'label' => 'Invoice Avg Shipping Tax Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_shipping_tax_amount)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_total_invoiced_cost',
            array(
                'label' => 'Avg Total Invoiced Cost',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_grand_total)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'invoice_avg_grand_total',
            array(
                'label' => 'Invoice Avg Grand Total',
                'type' => 'currency',
                'expression' => 'AVG(sales_invoice_table.base_grand_total)',
                'table' => 'sales/invoice',
            )
        ) ->addColumn(
            'creditmemo_quantity',
            array(
                'label' => 'Number of Credit Memos',
                'type' => 'number',
                'expression' => 'COUNT(DISTINCT(sales_creditmemo_table.entity_id))',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_sum_discount_amount',
            array(
                'label' => 'Credit Memo Discount Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_creditmemo_table.base_discount_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_sum_shipping_amount',
            array(
                'label' => 'Credit Memo Shipping Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_creditmemo_table.base_shipping_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_sum_tax_amount',
            array(
                'label' => 'Credit Memo Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_creditmemo_table.base_tax_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_sum_shipping_tax_amount',
            array(
                'label' => 'Credit Memo Shipping Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_creditmemo_table.base_shipping_tax_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_sum_subtotal',
            array(
                'label' => 'Credit Memo Subtotal',
                'type' => 'currency',
                'expression' => 'SUM(sales_creditmemo_table.base_subtotal)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_sum_grand_total',
            array(
                'label' => 'Credit Memo Grand Total',
                'type' => 'currency',
                'expression' => 'SUM(sales_creditmemo_table.base_grand_total)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_avg_discount_amount',
            array(
                'label' => 'Credit Memo Avg Discount Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_creditmemo_table.base_discount_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_avg_shipping_amount',
            array(
                'label' => 'Credit Memo Avg Shipping Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_creditmemo_table.base_shipping_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_avg_tax_amount',
            array(
                'label' => 'Credit Memo Avg Tax Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_creditmemo_table.base_tax_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_avg_shipping_tax_amount',
            array(
                'label' => 'Credit Memo Avg Shipping Tax Amount',
                'type' => 'currency',
                'expression' => 'AVG(sales_creditmemo_table.base_shipping_tax_amount)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_avg_total_ordered_cost',
            array(
                'label' => 'Avg Total Ordered Cost',
                'type' => 'currency',
                'expression' => 'AVG(sales_creditmemo_table.base_grand_total)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_avg_grand_total',
            array(
                'label' => 'Credit Memo Avg Grand Total',
                'type' => 'currency',
                'expression' => 'AVG(sales_creditmemo_table.base_grand_total)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'sum_orders_qty',
            array(
                'label' => 'Number of Orders',
                'type' => 'quantity',
                'expression' => 'COUNT(DISTINCT sales_creditmemo_table.order_id)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_increment_id',
            array(
                'label' => 'Credit Memos #',
                'type' => 'text',
                'expression' => 'GROUP_CONCAT(sales_creditmemo_table.increment_id)',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'creditmemo_increment',
            array(
                'label' => 'Credit Memo #',
                'type' => 'text',
                'expression' => 'sales_creditmemo_table.increment_id',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'invoice_increment_id',
            array(
                'label' => 'Invoice #',
                'type' => 'text',
                'expression' => 'GROUP_CONCAT(sales_invoice_table.increment_id)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'order_increment_ids',
            array(
                'label' => 'Orders #',
                'type' => 'text',
                'expression' => 'GROUP_CONCAT(DISTINCT sales_order_table.increment_id)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'order_increment_id',
            array(
                'label' => 'Order #',
                'type' => 'text',
                'expression' => 'sales_order_table.increment_id',
                'table' => 'sales/order',
            )
        )->addColumn(
            'country_id',
            array(
                'label' => 'Country',
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_country')->toOptionHash(),
                'expression' => 'sales_order_address_table.country_id',
                'table' => 'sales/order_address',
            )
        )->addColumn(
            'shipping_address',
            array(
                'label'        => 'Shipping Address',
                'type'         => 'text',
                'expression'   => 'CONCAT_WS(", ", sales_order_shipping_address_table.street, sales_order_shipping_address_table.city, sales_order_shipping_address_table.region, sales_order_shipping_address_table.postcode, sales_order_shipping_address_table.country_id)',
                'table_method' => 'advr/report_table_shippingAddress',
            )
        )->addColumn(
            'billing_address',
            array(
                'label'        => 'Billing Address',
                'type'         => 'text',
                'expression'   => 'CONCAT_WS(", ", sales_order_address_table.street, sales_order_address_table.city, sales_order_address_table.region, sales_order_address_table.postcode, sales_order_address_table.country_id)',
                'table'        => 'sales/order_address',
            )
        )->addColumn(
            'shipping_city',
            array(
                'label'        => 'Shipping City',
                'type'         => 'text',
                'expression'   => 'sales_order_shipping_address_table.city',
                'table_method' => 'advr/report_table_shippingAddress',
            )
        )->addColumn(
            'shipping_telephone',
            array(
                'label'        => 'Shipping Telephone',
                'type'         => 'text',
                'expression'   => 'sales_order_shipping_address_table.telephone',
                'table_method' => 'advr/report_table_shippingAddress',
            )
        )->addColumn(
            'state',
            array(
                'label' => 'State',
                'type' => 'text',
                // 'expression' => 'advr_postcode_table.state',
                'expression' => 'IFNULL(advr_postcode_table.state, sales_order_address_table.region)',
                'table' => 'advr/postcode',
            )
        )->addColumn(
            'province',
            array(
                'label' => 'Province',
                'type' => 'text',
                'expression' => 'advr_postcode_table.province',
                'table' => 'advr/postcode',
            )
        )->addColumn(
            'place',
            array(
                'label' => 'City / Place',
                'type' => 'text',
                // 'expression' => 'advr_postcode_table.place',
                'expression' => 'IFNULL(advr_postcode_table.place, sales_order_address_table.city)',
                'table' => 'advr/postcode',
            )
        )->addColumn(
            'lat',
            array(
                'label' => false,
                'expression' => 'advr_postcode_table.lat',
                'table' => 'advr/postcode',
            )
        )->addColumn(
            'lng',
            array(
                'label' => false,
                'expression' => 'advr_postcode_table.lng',
                'table' => 'advr/postcode',
            )
        )->addColumn(
            'postcode',
            array(
                'label' => 'Postcode',
                'type' => 'text',
                // 'expression' => 'advr_postcode_table.postcode',
                'expression' => 'IFNULL(advr_postcode_table.postcode, sales_order_address_table.postcode)',
                'table' => 'advr/postcode',
            )
        )->addColumn(
            'period',
            array(
                'label' => false,
                'expression_method' => 'getPeriodExpression',
                'table' => 'sales/order',
            )
        )->addColumn(
            'invoice_period',
            array(
                'label' => false,
                'expression_method' => 'getInvoicePeriodExpression',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'creditmemo_period',
            array(
                'label' => false,
                'expression_method' => 'getCreditmemoPeriodExpression',
                'table' => 'sales/creditmemo',
            )
        )->addColumn(
            'period_of_sale',
            array(
                'label' => false,
                'expression_method' => 'getSalePeriodExpression',
                'table' => 'sales/order',
            )
        )->addColumn(
            'hour_of_day',
            array(
                'label' => 'Hour of Day',
                'type' => 'text',
                'expression_method' => 'getHourOfDayExpression',
                'table' => 'sales/order',
            )
        )->addColumn(
            'month_of_year',
            array(
                'label' => 'Month',
                'type' => 'text',
                'expression_method' => 'getMonthOfYearExpression',
                'table' => 'sales/order',
            )
        )->addColumn(
            'day_of_week',
            array(
                'label' => 'Day of Week',
                'type' => 'text',
                'expression_method' => 'getDayOfWeekExpression',
                'table' => 'sales/order',
            )
        )->addColumn(
            'payment_method',
            array(
                'label' => 'Payment Method',
                'expression' => 'sales_order_payment_table.method',
                'table' => 'sales/order_payment',
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_paymentType')->toOptionHash(),
            )
        )->addColumn(
            'cc_type',
            array(
                'label' => 'Credit Card Type',
                'expression' => 'sales_order_payment_table.cc_type',
                'table' => 'sales/order_payment',
            )
        )->addColumn(
            'customer_group_id',
            array(
                'label' => 'Customer Group',
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_customerGroup')->toOptionHash(),
                'expression' => 'sales_order_table.customer_group_id',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_group_code',
            array(
                'label' => false,
                'expression' => 'customer_customer_group_table.customer_group_code',
                'table' => 'customer/customer_group',
            )
        )->addColumn(
            'coupon_code',
            array(
                'label' => 'Coupon Code',
                'type' => 'text',
                'expression' => 'sales_order_table.coupon_code',
                'table' => 'sales/order',
            )
        )->addColumn(
            'salesrule_rule_id',
            array(
                'label' => 'Rule Id',
                'expression' => 'salesrule_rule_table.rule_id',
                'table' => 'salesrule/rule',
            )
        )->addColumn(
            'salesrule_rule_name',
            array(
                'label' => 'Rule Title',
                'expression' => 'salesrule_rule_table.name',
                'table' => 'salesrule/rule',
            )
        )->addColumn(
            'taxrate_tax_code',
            array(
                'label' => 'Tax Identifier',
                'type' => 'text',
                'expression' => 'sales_order_tax_table.code',
                'table' => 'sales/order_tax',
            )
        )->addColumn(
            'taxrate_tax_title',
            array(
                'label' => 'Tax Title',
                'type' => 'text',
                'expression' => 'sales_order_tax_table.title',
                'table' => 'sales/order_tax',
            )
        )->addColumn(
            'taxrate_tax_percent',
            array(
                'label' => 'Tax Rate',
                'type' => 'number',
                'expression' => 'sales_order_tax_table.percent',
                'table' => 'sales/order_tax',
            )
        )->addColumn(
            'is_new_customer',
            array(
                'label' => false,
                'expression' => $isNewCustomer,
                'table' => 'customer/entity',
            )
        )->addColumn(
            'quantity_by_new_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isNewCustomer.', 1, 0))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'quantity_by_registered_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isRegisteredCustomer.', 1, 0))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'sum_grand_total_by_new_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isNewCustomer.', base_grand_total, 0))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'sum_grand_total_by_registered_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isRegisteredCustomer.', base_grand_total, 0))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'quantity_by_returning_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isNewCustomer.', 0, 1))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'quantity_by_unregistered_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isRegisteredCustomer.', 0, 1))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'sum_grand_total_by_returning_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isNewCustomer.', 0, base_grand_total))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'sum_grand_total_by_unregistered_customers',
            array(
                'label' => false,
                'expression' => 'SUM(IF('.$isRegisteredCustomer.', 0, base_grand_total))',
                'table' => 'customer/entity',
            )
        )->addColumn(
            'customer_email',
            array(
                'label' => 'Email',
                'expression' => 'sales_order_table.customer_email',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_id',
            array(
                'label' => false,
                'expression' => 'sales_order_table.customer_id',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_is_guest',
            array(
                'label' => false,
                'expression' => 'sales_order_table.customer_is_guest',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_name',
            array(
                'label' => 'Full Name',
                'expression' => 'CONCAT(
                    sales_order_table.customer_firstname,
                    " ",
                    sales_order_table.customer_lastname)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_firstname',
            array(
                'label' => 'First Name',
                'expression' => 'sales_order_table.customer_firstname',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_lastname',
            array(
                'label' => 'Last Name',
                'expression' => 'sales_order_table.customer_lastname',
                'table' => 'sales/order',
            )
        )->addColumn(
            'customer_company',
            array(
                'label' => 'Customer Company',
                'expression' => 'sales_order_address_table.company',
                'table' => 'sales/order_address',
            )
        )->addColumn(
            'category_id',
            array(
                'label' => 'Category Id',
                'type' => 'number',
                'expression' => 'catalog_category_table.entity_id',
                'table' => 'catalog/category',
            )
        )->addColumn(
            'category_level',
            array(
                'label' => false,
                'expression' => 'catalog_category_table.level',
                'table' => 'catalog/category',
                'type' => 'number',
            )
        )->addColumn(
            'category_name',
            array(
                'label' => 'Category Name',
                'expression' => 'catalog_category_name_table.value',
                'table_method' => 'advr/report_table_categoryName',
            )
        )->addColumn(
            'category_path',
            array(
                'label' => false,
                'expression' => 'catalog_category_table.path',
                'table' => 'catalog/category',
            )
        )->addColumn(
            'category_is_active',
            array(
                'label' => 'Category Is Active',
                'expression' => 'catalog_category_is_active_table.value',
                'table_method' => 'advr/report_table_categoryIsActive',
            )
        )->addColumn(
            'qty_distinct_products',
            array(
                'label' => 'Qty Distinct Products',
                'type' => 'number',
                'expression' => 'COUNT(DISTINCT(sales_order_item_table.product_id))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_qty_ordered',
            array(
                'label' => 'Qty Ordered',
                'type' => 'number',
                'expression' => 'SUM(sales_order_item_table.qty_ordered)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.qty_ordered,sales_order_item_table.qty_ordered))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_qty_refunded',
            array(
                'label' => 'Qty Refunded',
                'type' => 'number',
                'expression' => 'SUM(sales_order_item_table.qty_refunded)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.qty_refunded,sales_order_item_table.qty_refunded))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_amount_refunded',
            array(
                'label' => 'Amount Refunded',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.base_amount_refunded)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_amount_refunded,sales_order_item_table.base_amount_refunded))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_row_total',
            array(
                'label' => 'Row Total',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.base_row_total)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_row_total,sales_order_item_table.base_row_total))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_row_invoiced',
            array(
                'label' => 'Row Invoiced',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.base_row_invoiced)',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'avg_item_base_price',
            array(
                'label' => 'Price',
                'type' => 'currency',
                'expression' => 'AVG(sales_order_item_table.base_price)',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_tax_amount',
            array(
                'label' => 'Tax Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.base_tax_amount)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_tax_amount,sales_order_item_table.base_tax_amount))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'sum_item_discount_amount',
            array(
                'label' => 'Discount Amount',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.base_discount_amount)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_discount_amount,sales_order_item_table.base_discount_amount))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'avg_shipping_time',
            array(
                'label' => false,
                'expression' => "AVG($shippingTime)",
                'table' => 'sales/shipment',
            )
        )->addColumn(
            'quantity_shipping_time_0_1',
            array(
                'label' => false,
                'expression' => "SUM(IF($shippingTime <= 3600, 1, 0))",
                'table' => 'sales/shipment',
            )
        )->addColumn(
            'quantity_shipping_time_1_24',
            array(
                'label' => false,
                'expression' => "SUM(IF($shippingTime > 3600 AND $shippingTime <= 86400, 1, 0))",
                'table' => 'sales/shipment',
            )
        )->addColumn(
            'quantity_shipping_time_24_48',
            array(
                'label' => false,
                'expression' => "SUM(IF($shippingTime > 86400 AND $shippingTime <= 172800, 1, 0))",
                'table' => 'sales/shipment',
            )
        )->addColumn(
            'quantity_shipping_time_48_72',
            array(
                'label' => false,
                'expression' => "SUM(IF($shippingTime > 172800 AND $shippingTime <= 259200, 1, 0))",
                'table' => 'sales/shipment',
            )
        )->addColumn(
            'quantity_shipping_time_72_',
            array(
                'label' => false,
                'expression' => "SUM(IF($shippingTime > 259200, 1, 0))",
                'table' => 'sales/shipment',
            )
        )->addColumn(
            'product_sku',
            array(
                'label' => 'SKU',
                'type' => 'array',
                'expression' => 'catalog_product_table.sku',
                'table' => 'catalog/product',
            )
        )->addColumn(
            'product_id',
            array(
                'label' => 'ID',
                'type' => 'number',
                'expression' => 'catalog_product_table.entity_id',
                'table' => 'catalog/product',
            )
        )->addColumn(
            'product_name',
            array(
                'label' => 'Name',
                'type' => 'text',
                'expression' => 'catalog_product_name_table.value',
                'table_method' => 'advr/report_table_productAttribute',
                'table_args' => array(
                    'attribute' => 'name',
                ),
            )
        )->addColumn(
            'product_default_name',
            array(
                'label' => 'Product Name',
                'expression' => 'catalog_product_default_name_table.value',
                'table_method' => 'advr/report_table_productName',
            )
        )->addColumn(
            'product_attribute_set_id',
            array(
                'label' => 'Attribute Set',
                'type' => 'options',
                'expression' => 'catalog_product_table.attribute_set_id',
                'table' => 'catalog/product',
                'options' => $this->getAttributeSetOptions(),
            )
        )->addColumn(
            'product_stock_qty',
            array(
                'label' => 'Stock Qty',
                'type' => 'number',
                'expression' => 'cataloginventory_stock_item_table.qty',
                'table' => 'cataloginventory/stock_item',
            )
        )->addColumn(
            'product_is_in_stock',
            array(
                'label' => 'Stock Availability',
                'type' => 'number',
                'expression' => 'cataloginventory_stock_item_table.is_in_stock',
                'table' => 'cataloginventory/stock_item',
            )
        )->addColumn(
            'item_gross_profit_percent',
            array(
                'label' => 'Gross Profit',
                'type' => 'currency',
                // getExpression() needs external brackets
                'expression' => '(SUM(sales_order_item_table.base_row_total - sales_order_item_table.qty_ordered * IFNULL(sales_order_item_table.base_cost,0))/SUM(sales_order_item_table.base_row_total))',
                'expression_child' => '(SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_row_total,sales_order_item_table.base_row_total) - IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.qty_ordered,sales_order_item_table.qty_ordered) * IFNULL(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_cost,sales_order_item_table.base_cost),0))/SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_row_total,sales_order_item_table.base_row_total)))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'item_gross_profit',
            array(
                'label' => 'Gross Profit',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.base_row_total - sales_order_item_table.qty_ordered * IFNULL(sales_order_item_table.base_cost,0))',
                'expression_child' => '(SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_row_total,sales_order_item_table.base_row_total) - IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.qty_ordered,sales_order_item_table.qty_ordered) * IFNULL(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_cost,sales_order_item_table.base_cost),0)))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'item_cost',
            array(
                'label' => 'Cost',
                'type' => 'currency',
                'expression' => 'SUM(sales_order_item_table.qty_ordered * sales_order_item_table.base_cost)',
                'expression_child' => 'SUM(IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.qty_ordered,sales_order_item_table.qty_ordered) * IF(sales_order_item_table.product_type = "bundle",sales_order_item_parent_table.base_cost,sales_order_item_table.base_cost))',
                'table' => 'sales/order_item',
            )
        )->addColumn(
            'shipping_method',
            array(
                'label' => 'Shipping Method',
                'expression' => 'sales_order_table.shipping_method',
                'table' => 'sales/order',
            )
        )->addColumn(
            'created_at',
            array(
                'label' => 'Created At',
                'expression' => 'catalog_product_table.created_at',
                'table' => 'catalog/product',
                'type' => 'datetime',
            )
        )->addColumn(
            'updated_at',
            array(
                'label' => 'Updated At',
                'expression' => 'catalog_product_table.updated_at',
                'table' => 'catalog/product',
                'type' => 'datetime',
            )
        )->addColumn(
            'qty_of_unique_register_customers',
            array(
                'label' => 'Qty of Unique Registered Customers',
                'type' => 'number',
                'expression' => 'COUNT(DISTINCT(sales_order_table.customer_id))',
                'table' => 'sales/order',
            )
        )->addColumn(
            'order_emails',
            array(
                'label' => false,
                'expression' => 'CONCAT_WS(":",
                        GROUP_CONCAT(sales_order_table.customer_is_guest SEPARATOR "^"),
                        GROUP_CONCAT(sales_order_table.customer_email SEPARATOR "^")
                    )',
                'table' => 'sales/order',
            )
        )->addColumn(
            'orders',
            array(
                'label' => false,
                'expression' => 'GROUP_CONCAT(DISTINCT sales_order_table.entity_id)',
                'table' => 'sales/order',
            )
        )->addColumn(
            'invoices',
            array(
                'label' => false,
                'expression' => 'GROUP_CONCAT(DISTINCT sales_invoice_table.entity_id)',
                'table' => 'sales/invoice',
            )
        )->addColumn(
            'last_login_at',
            array(
                'label' => false,
                'expression' => 'MAX(log_customer_table.login_at)',
                'table' => 'log/customer',
            )
        );

        $attributes = Mage::getSingleton('advr/system_config_source_productAttribute')->toOptionHash();

        foreach ($attributes as $attrCode => $attrLabel) {
            if ($attrCode === 'sku') {
                continue;
            }

            $options = Mage::helper('advr')->getAttributeOptionHash($attrCode);

            $type = 'text';
            if ($options) {
                $type = 'options';
            }

            $this->addColumn(
                'product_attribute_'.$attrCode,
                array(
                    'label' => $attrLabel,
                    'type' => $type,
                    'options' => $options,
                    'expression' => $this->prepareExpression('catalog_product_'.$attrCode.'_table.value'),
                    'table' => 'catalog/product',
                    'table_method' => 'advr/report_table_productAttribute',
                    'table_args' => array(
                        'attribute' => $attrCode,
                    ),
                )
            );
        }
    }

    public function getAttributeSetOptions()
    {
        $options = Mage::getModel('eav/entity_attribute_set')->getCollection()
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->toOptionHash();

        return $options;
    }

    public function getPeriodExpression()
    {
        return $this->_getRangeExpressionForAttribute(
            $this->getFilterData()->getRange(),
            $this->getTZDate('sales_order_table.created_at', true)
        );
    }

    public function getInvoicePeriodExpression()
    {
        return $this->_getRangeExpressionForAttribute(
            $this->getFilterData()->getRange(),
            $this->getTZDate('sales_invoice_table.created_at')
        );
    }

    public function getCreditmemoPeriodExpression()
    {
        return $this->_getRangeExpressionForAttribute(
            $this->getFilterData()->getRange(),
            $this->getTZDate('sales_creditmemo_table.created_at')
        );
    }

    public function getSalePeriodExpression()
    {
        return $this->_getRangeExpressionForAttribute(
            $this->getFilterData()->getRange(),
            $this->getTZDate('sales_order_table.created_at')
        );
    }

    public function getHourOfDayExpression()
    {
        return 'HOUR('.$this->getTZDate('sales_order_table.created_at', true).')';
    }

    public function getMonthOfYearExpression()
    {
        return 'MONTH('.$this->getTZDate('sales_order_table.created_at', true).')';
    }

    public function getDayOfWeekExpression()
    {
        if (Mage::getStoreConfig('general/locale/firstday') === '0') {
            return new Zend_Db_Expr('DAYOFWEEK('.$this->getTZDate('sales_order_table.created_at', true).')');
        }

        return new Zend_Db_Expr('WEEKDAY('.$this->getTZDate('sales_order_table.created_at', true).')');
    }

    /**
     * Add relations associated with used sales source.
     *
     * @param string $salesSource
     *
     * @return $this
     */
    protected function addSalesSourceRelations($salesSource)
    {
        if (isset($this->relationsBySalesSource[$salesSource])) {
            $this->relations + $this->relationsBySalesSource[$salesSource];
        }

        return $this;
    }

    /**
     * Apply filters to collection.
     *
     * @param Varien_Object $data - filter data
     * @param bool          $filterByStatus
     * @param bool          $timeOfGroup
     * @param bool          $joinSalesOrder
     * @param bool          $checkDateForTZ
     *
     * @return $this
     */
    public function setFilterData(
        $data,
        $filterByStatus = true,
        $timeOfGroup = true,
        $joinSalesOrder = true,
        $checkDateForTZ = false
    ) {
        parent::setFilterData($data);

        $this->filterData = $data;
        $this->addSalesSourceRelations($data->getSalesSource());

        if ($joinSalesOrder) {
            $this->joinRelatedDependencies('sales/order');
        }

        $conditions = array();
        $conditions = $this->assembleDateCondition($conditions, $this->filterData, $timeOfGroup, $checkDateForTZ);
        $conditions = $this->assembleStoreCondition($conditions, $this->filterData);
        $conditions = $this->assembleStatusCondition($conditions, $filterByStatus);
        $conditions = $this->assembleIpCondition($conditions, $this->filterData);

        foreach ($conditions as $condition) {
            $this->getSelect()->where($condition);
        }

        foreach (array_keys($data->getData()) as $column) {
            if (isset($this->columns[$column])) {
                $this->selectColumns($column);
                $cond = $this->columns[$column]->getFilter()->getCondition();
                $this->addFieldToFilter($column, $cond);
            }
        }

        return $this;
    }

    /**
     * Create and add date conditions.
     *
     * @param array         $conditions
     * @param Varien_Object $filterData
     * @param               $timeOfGroup
     * @param               $checkDateForTZ
     *
     * @return array - conditions
     */
    private function assembleDateCondition(array $conditions, Varien_Object $filterData, $timeOfGroup, $checkDateForTZ)
    {
        if ($filterData->getFrom()) {
            if ($checkDateForTZ) {
                $conditions[] =
                    $this->getTZDate(
                        $this->getRangeFilterTable().'.created_at',
                        $timeOfGroup,
                        $filterData->getFrom(),
                        $filterData->getTo()
                    )
                    ." >= '"
                    .$filterData->getFrom()."'";
            } else {
                $conditions[] = $this->getTZDate($this->getRangeFilterTable().'.created_at', $timeOfGroup)
                    ." >= '"
                    .$filterData->getFrom()."'";
            }
        }

        if ($filterData->getTo()) {
            if ($checkDateForTZ) {
                $conditions[] =
                    $this->getTZDate(
                        $this->getRangeFilterTable().'.created_at',
                        $timeOfGroup,
                        $filterData->getFrom(),
                        $filterData->getTo()
                    )
                    ." < '"
                    .$filterData->getTo()."'";
            } else {
                $conditions[] = $this->getTZDate($this->getRangeFilterTable().'.created_at', $timeOfGroup)
                    ." < '"
                    .$filterData->getTo()."'";
            }
        }

        return $conditions;
    }

    /**
     * Create and add store condition.
     *
     * @param array         $conditions
     * @param Varien_Object $filterData
     *
     * @return array - conditions
     */
    private function assembleStoreCondition(array $conditions, Varien_Object $filterData)
    {
        if (count($filterData->getStoreIds())) {
            $conditions[] = 'sales_order_table.store_id IN('.implode(',', $filterData->getStoreIds()).')';
        }

        return $conditions;
    }

    /**
     * Create and add store condition.
     *
     * @param array $conditions
     * @param bool  $filterByStatus
     *
     * @return array - conditions
     */
    private function assembleStatusCondition(array $conditions, $filterByStatus)
    {
        if ($filterByStatus) {
            $statuses = Mage::getSingleton('advr/config')->getProcessOrderStatuses();
            foreach ($statuses as $idx => $status) {
                $statuses[$idx] = "'$status'";
            }

            $conditions[] = '(sales_order_table.status IN('.implode(',', $statuses).')
                OR sales_order_table.status IS NULL)';
        }

        return $conditions;
    }

    /**
     * Create and add store condition.
     *
     * @param array         $conditions
     * @param Varien_Object $filterData
     *
     * @return array - conditions
     */
    private function assembleIpCondition(array $conditions, Varien_Object $filterData)
    {
        if($filterData->getRemoteIp()){
            switch ($filterData->getRemoteIp()) {
                case 1:
                    $conditions[] = ' (sales_order_table.remote_ip IS NULL) ';
                    break;
                case 2;
                    $conditions[] = ' (sales_order_table.remote_ip IS NOT NULL) ';
                    break;
            }
        }

        return $conditions;
    }
}
