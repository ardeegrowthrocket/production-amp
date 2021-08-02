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



class Mirasvit_Advr_Block_Adminhtml_Order_Customer extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sales By Customer'));

        return $this;
    }

    protected function prepareChart()
    {
        return $this;
    }

    protected function prepareToolbar()
    {
        $this->initToolbar()
            ->setSalesSourceVisibility(true);

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid()
            ->setDefaultSort($this->getColumn('sum_grand_total'))
            ->setDefaultDir('desc')
            ->setRowUrlCallback(array($this, 'rowUrlCallback'));

        return $this;
    }

    protected function getGroupByColumn()
    {
        return $this->getColumn('customer_email');
    }

    protected function _prepareCollection()
    {
        $collection = parent::_prepareCollection();

        $collection->selectColumns(array_merge(
                array('customer_firstname', 'customer_lastname', 'customer_id','orders'),
                $this->getVisibleColumns()
            ))
            ->addFieldToFilter('customer_group_id', array('gt' => 0));

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'customer_email' => array(
                'header' => 'Customer',
                'totals_label' => 'Total',
                'filter_totals_label' => 'Subtotal',
                'frame_callback' => array(Mage::helper('advr/callback'), 'linkToCustomer'),
                'chart' => true,
                self::KEEP => true
            ),

            'customer_name' => array(
                'header' => 'Customer Name',
                self::KEEP => true
            ),

            'customer_group_id' => array(
                'header' => 'Customer Group',
                'chart' => false,
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_customerGroup')->toOptionHash(),
                self::KEEP => true
            ),

            'customer_company' => array(
                'header' => 'Customer Company',
                'filter' => false,
                'hidden' => true,
                self::KEEP => true
            ),

            'last_login_at' => array(
                'header' => 'Last Login Date',
                'type' => 'date',
            ),

        );

        $columns += $this->getOrderTableColumns(true);

        $columns = $this->convertColumnsToSalesSource($columns);

        $columns['actions'] = array(
            'header' => 'Actions',
            'renderer' => 'Mirasvit_Advr_Block_Adminhtml_Block_Grid_Renderer_PostAction',
        );

        return $columns;
    }

    public function rowUrlCallback($row)
    {
        if ($row->getCustomerId()) {
            return $this->getUrl('adminhtml/customer/edit', array('id' => $row->getCustomerId()));
        }

        return false;
    }
}
