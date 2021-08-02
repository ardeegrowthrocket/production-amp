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



class Mirasvit_Advd_Block_Adminhtml_Widget_Order_Coupon extends Mirasvit_Advd_Block_Adminhtml_Widget_Abstract_Grid
{
    public function getGroup()
    {
        return 'Sales';
    }

    public function getName()
    {
        return 'Sales by Coupon';
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
            'limit',
            'text',
            array(
                'name' => 'limit',
                'label' => Mage::helper('advd')->__('Number Of Coupons'),
                'value' => $this->getParam('limit', 5),
            )
        );

        return $this;
    }

    public function activeFilters()
    {
        return array();
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
            ->setFilterData($filterData, true, false)
            ->selectColumns(array('coupon_code', 'quantity', 'sum_total_qty_ordered', 'sum_subtotal', 'sum_grand_total'))
            ->groupByColumn('coupon_code')
            ->setOrder('quantity', 'desc');

        if (count($this->getParam('store_ids'))) {
            $collection->addAttributeToFilter('store_id', array('in' => $this->getParam('store_ids')));
        }

        if ($this->getParam('customer_groups')) {
            $collection->addAttributeToFilter('customer_group_id', array('in' => $this->getParam('customer_groups')));
        }


        $grid->setCollection($collection);

        return $this;
    }

    protected function _prepareColumns($grid)
    {
        $grid->addColumn('coupon_code', array(
            'header' => Mage::helper('advd')->__('Coupon Code'),
            'sortable' => false,
            'index' => 'coupon_code',
            'column_css_class' => 'nobr',
        ));

        $grid->addColumn('quantity', array(
            'header' => Mage::helper('advd')->__('Number Of Orders'),
            'sortable' => false,
            'type' => 'number',
            'index' => 'quantity',
            'column_css_class' => 'nobr',
        ));

        $grid->addColumn('sum_total_qty_ordered', array(
            'header'    => Mage::helper('advd')->__('Items Ordered'),
            'sortable'  => false,
            'type' => 'number',
            'index'     => 'sum_total_qty_ordered',
            'column_css_class'  => 'nobr',
        ));

        $baseCurrencyCode = Mage::app()->getStore((int) $this->getParam('store'))->getBaseCurrencyCode();

        $grid->addColumn('sum_subtotal', array(
            'header' => Mage::helper('advd')->__('Subtotal'),
            'type' => 'currency',
            'sortable' => false,
            'index' => 'sum_subtotal',
            'currency_code' => $baseCurrencyCode,
            'column_css_class' => 'nobr',
        ));

        $grid->addColumn('sum_grand_total', array(
            'header' => Mage::helper('advd')->__('Grand Total'),
            'type' => 'currency',
            'sortable' => false,
            'index' => 'sum_grand_total',
            'currency_code' => $baseCurrencyCode,
            'column_css_class' => 'nobr',
        ));

        $grid->setFilterVisibility(false);
        $grid->setPagerVisibility(false);
        $grid->setDefaultLimit($this->getParam('limit', 5));

        return $this;
    }

}
