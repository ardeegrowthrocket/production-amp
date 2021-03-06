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



class Mirasvit_Advd_Block_Adminhtml_Widget_Order_Grid extends Mirasvit_Advd_Block_Adminhtml_Widget_Abstract_Grid
{
    public function getGroup()
    {
        return 'Sales';
    }

    public function getName()
    {
        return 'Last Orders';
    }

    public function prepareOptions()
    {
        $this->form->addField(
            'limit',
            'text',
            array(
                'name' => 'limit',
                'label' => Mage::helper('advd')->__('Number Of Orders'),
                'value' => $this->getParam('limit', 5),
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
        $collection = Mage::getModel('sales/order')
            ->getResourceCollection();
        $collection->setOrder('created_at', 'desc');

        if (count($this->getParam('store_ids'))) {
            $collection->addAttributeToFilter('store_id', array('in' => $this->getParam('store_ids')));
        }

//        if ($this->getParam('customer_groups')) {
//            $collection->addAttributeToFilter('customer_group_id', array('in' => $this->getParam('customer_groups')));
//        }


        $grid->setCollection($collection);

        return $this;
    }

    protected function _prepareColumns($grid)
    {
        $grid->addColumn('increment_id', array(
            'header' => Mage::helper('advd')->__('Order #'),
            'sortable' => false,
            'index' => 'increment_id',
            'column_css_class' => 'nobr',
        ));

        $grid->addColumn('customer_firstname', array(
            'header' => Mage::helper('advd')->__('Customer'),
            'sortable' => false,
            'index' => 'customer_firstname',
            'frame_callback' => array($this, '_prepareCustomerName'),
            'column_css_class' => 'nobr',
        ));

        $grid->addColumn('customer_group_id', array(
            'header'    => Mage::helper('advd')->__('Customer Group'),
            'sortable'  => false,
            'index'     => 'customer_group_id',
            'frame_callback' => array($this, '_prepareCustomerGroupNames'),
            'column_css_class'  => 'nobr',
        ));

        $grid->addColumn('total_qty_ordered', array(
            'header' => Mage::helper('advd')->__('Items'),
            'align' => 'right',
            'type' => 'number',
            'sortable' => false,
            'index' => 'total_qty_ordered',
            'column_css_class' => 'nobr',
        ));

        $baseCurrencyCode = Mage::app()->getStore((int) $this->getParam('store'))->getBaseCurrencyCode();

        $grid->addColumn('grand_total', array(
            'header' => Mage::helper('advd')->__('Grand Total'),
            'align' => 'right',
            'sortable' => false,
            'type' => 'currency',
            'currency_code' => $baseCurrencyCode,
            'index' => 'grand_total',
            'column_css_class' => 'nobr',
        ));

        $grid->setFilterVisibility(false);
        $grid->setPagerVisibility(false);
        $grid->setDefaultLimit($this->getParam('limit', 5));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
    }

    public function _prepareCustomerName($value, $row, $column)
    {
        return $row->getCustomerFirstname().' '.$row->getCustomerLastname();
    }

    public function _prepareCustomerGroupNames($value, $row, $column)
    {
        return $this->getCustomerGroups($row->getCustomerGroupId());
    }
}
