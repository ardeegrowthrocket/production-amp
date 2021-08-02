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



class Mirasvit_Advr_Block_Adminhtml_Catalog_Abstract extends Mirasvit_Advr_Block_Adminhtml_Block_Container
{
    public function getTotals()
    {
        return $this->getCollection()->getTotals();
    }

    public function getBaseProductColumns($includePercentOfTotal = false)
    {
        $columns = array();

        if ($includePercentOfTotal) {
            $columns['percent'] = array(
                'header' => 'Number Of Orders, %',
                'type' => 'percent',
                'filter' => false,
                'index' => 'quantity',
                'frame_callback' => array(Mage::helper('advr/callback'), 'percent'),
            );
        }

        $columns['percent_ordered'] = array(
            'header' => 'QTY Ordered, %',
            'type' => 'percent',
            'filter' => false,
            'index' => 'sum_item_qty_ordered',
            'frame_callback' => array(Mage::helper('advr/callback'), 'percent'),
            'hidden' => true,
        );

        $columns['quantity'] = array(
            'header' => 'Number Of Orders',
            'type' => 'number',
            'sortable' => true,
            'chart' => false,
        );

        $columns['quantity_refunded'] = array(
            'header' => 'Number Of Refunded Orders',
            'type' => 'number',
            'sortable' => true,
            'chart' => false,
            'hidden' => true,
        );

        $columns['sum_item_qty_ordered'] = array(
            'header' => 'QTY Ordered',
            'type' => 'number',
            'sortable' => true,
            'chart' => false,
        );
        $columns['sum_item_qty_refunded'] = array(
            'header' => 'QTY Refunded',
            'type' => 'number',
            'sortable' => true,
            'chart' => false,
        );
        $columns['sum_item_tax_amount'] = array(
            'header' => 'Tax',
            'type' => 'currency',
            'sortable' => true,
            'chart' => false,
        );
        $columns['sum_item_discount_amount'] = array(
            'header' => 'Discount',
            'type' => 'currency',
            'sortable' => true,
            'chart' => false,
            'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
            'discount_from' => 'sum_item_row_total',
        );
        $columns['sum_item_amount_refunded'] = array(
            'header' => 'Refunded',
            'type' => 'currency',
            'sortable' => true,
            'chart' => false,
        );
        $columns['sum_item_row_total'] = array(
            'header' => 'Total',
            'type' => 'currency',
            'hidden' => false,
            'sortable' => true,
            'chart' => true,
        );
        $columns['item_gross_profit_percent'] = array(
            'header' => 'Gross profit, %',
            'type' => 'percent',
            'filter' => false,
            'frame_callback' => array(Mage::helper('advr/callback'), 'percent'),
            'hidden' => true,
        );
        $columns['item_gross_profit'] = array(
            'header' => 'Gross profit',
            'type' => 'currency',
            'filter' => false,
            'hidden' => true,
            'sortable' => true,
            //'frame_callback' => array(Mage::helper('advr/callback'), 'discount'),
            'discount_from' => 'sum_item_row_total',
        );
        $columns['item_cost'] = array(
            'header' => 'Cost',
            'type' => 'currency',
            'filter' => false,
            'hidden' => true,
            'sortable' => true,
        );
        $columns['created_at'] = array(
            'header' => 'Created At',
            'type' => 'datetime',
            'hidden' => true,
            'totals_label' => '',
        );
        $columns['updated_at'] = array(
            'header' => 'Updated At',
            'type' => 'datetime',
            'hidden' => true,
            'totals_label' => '',
        );
        $columns['order_increment_ids'] = array(
            'header' => 'Orders #',
            'hidden' => true,
            'filter' => false,
            'sortable' => false,
            'totals_label' => '',
        );

        return $columns;
    }

    /**
     * Collect and return product attributes as columns.
     *
     * @return array
     */
    public function getProductAttributeColumns()
    {
        $columns    = [];
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

            $columns['product_attribute_'.$attrCode] = array(
                'header' => Mage::helper('advr')->__($attrLabel),
                'type' => $type,
                'options' => $options,
                'hidden' => true
            );
        }

        return $columns;
    }
}
