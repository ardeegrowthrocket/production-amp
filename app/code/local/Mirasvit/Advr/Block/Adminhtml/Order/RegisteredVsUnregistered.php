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



class Mirasvit_Advr_Block_Adminhtml_Order_RegisteredVsUnregistered extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{

    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Registered vs Unregistered Customers'));

        return $this;
    }

    protected function prepareChart()
    {
        $this->setChartType('column');

        $this->initChart()
            ->setXAxisType('datetime')
            ->setXAxisField('period_of_sale');

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort('period_of_sale')
            ->setDefaultDir('asc')
            ->setDefaultLimit(100000)
            ->setPagerVisibility(false)// ->setFilterVisibility(false)
        ;

        return $this;
    }

    protected function prepareToolbar()
    {
        $this->initToolbar()
            ->setRangesVisibility(true);

        return $this;
    }

    protected function getGroupByColumn()
    {
        return 'period_of_sale';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_sales')
            ->setBaseTable('sales/order')
            ->setFilterData($this->getFilterData(), true, false)
            ->selectColumns($this->getVisibleColumns())
            ->groupByColumn($this->getGroupByColumn());

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'period_of_sale' => array(
                'header'         => 'Period',
                'type'           => 'text',
                'index'          => 'period_of_sale',
                'frame_callback' => array(Mage::helper('advr/callback'), 'period'),
                'totals_label'   => 'Total',
                'filter'         => false,
            ),
            'qty_of_unique_register_customers'  => array(
                'header' => 'Unique Registered Customers',
                'type'   => 'number',
            ),

            'quantity_by_registered_customers' => array(
                'header' => 'Orders by Registered',
                'type'   => 'number',
                'chart'  => true,
            ),

            'sum_grand_total_by_registered_customers' => array(
                'header' => 'Grand Total by Registered',
                'type'   => 'currency',
            ),

            'percent_registered_customers' => array(
                'header'         => 'Percent of registered',
                'type'           => 'percent',
                'index'          => 'quantity_by_registered_customers',
                'frame_callback' => array($this, 'percent'),
            ),

            'order_emails' => array(
                'header' => 'Unique Unregistered Customers',
                'frame_callback' => array($this, 'orderEmails'),
                'filter' => false,
            ),


            'quantity_by_unregistered_customers' => array(
                'header' => 'Orders by Unregistered',
                'type'   => 'number',
                'chart'  => true,
            ),

            'sum_grand_total_by_unregistered_customers' => array(
                'header' => 'Grand Total by Unregistered',
                'type'   => 'currency',
            ),

            'percent_unregistered_customers' => array(
                'header'         => 'Percent of unregistered',
                'type'           => 'percent',
                'index'          => 'quantity_by_unregistered_customers',
                'frame_callback' => array($this, 'percent'),
            ),
        );

        return $columns;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function percent($value, $row, $column)
    {
        $a = $row->getData('quantity_by_registered_customers');
        $b = $row->getData('quantity_by_unregistered_customers');

        if ($b > 0) {
            $result = $a / ($a + $b);
        } else {
            $result = 1;
        }

        if ($b == $value) {
            $result = 1 - $result;
        }

        return sprintf("%.1f %%", $result * 100);
    }

    public function orderEmails($value, $row, $column)
    {
        $data = array();

        $emails = $row->getData('order_emails');
        $rows = explode(':', $emails);

        if (count($rows) != 2) {
            return 0;
        }

        $isGuests = explode('^', $rows[0]);
        $orderEmails = explode('^', $rows[1]);

        $uniqueEmails = array();

        foreach ($isGuests as $i => $isGuest) {
            if ($isGuest && isset($orderEmails[$i]) && !in_array($orderEmails[$i], $uniqueEmails)) {
                $uniqueEmails[] = $orderEmails[$i];
            }
        }

        return count($uniqueEmails);
    }
}
