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



class Mirasvit_Advr_Block_Adminhtml_Customer_Customer extends Mirasvit_Advr_Block_Adminhtml_Block_Container
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Customers'));

        return $this;
    }

    protected function prepareChart()
    {
        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid();

        return $this;
    }

    protected function prepareToolbar()
    {
        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_customers')
            ->increaseGroupConcatMaxLen()
            ->setBaseTable('customer/entity')
            ->setFilterData($this->getFilterData())
            ->selectColumns('customer_id')
            ->selectColumns(array_merge(array('quantity'), $this->getVisibleColumns()))
            ->groupByColumn('customer_id');

        return $collection;
    }

    public function getColumns()
    {
        $columns = array(
            'email' => array(
                'header' => 'Email',
                'type' => 'text',
                'totals_label' => 'Total',
                'filter_totals_label' => 'Subtotal',
                'link_callback' => array($this, 'customerCallBack'),
            ),

            'customer_firstname' => array(
                'header' => 'First Name',
                'type' => 'text',
            ),
            'customer_lastname' => array(
                'header' => 'Last Name',
                'type' => 'text',
            ),
            'customer_group_id' => array(
                'header' => 'Customer Group',
                'type' => 'options',
                'options' => Mage::getSingleton('advr/system_config_source_customerGroup')->toOptionHash(),
            ),

            'customer_created_at' => array(
                'header' => 'Account Created',
                'type' => 'date',
            ),

            'last_order_at' => array(
                'header' => 'Last Order Date',
                'type' => 'date',
            ),
            'last_login_at' => array(
                'header' => 'Last Login Date',
                'type' => 'date',
            ),
            'products' => array(
                'header' => 'Purchased Products',
                'frame_callback' => array($this, 'products'),
                'hidden' => true,
                'filter_condition_callback' => array($this, 'productFilter'),
            ),
            'quantity' => array(
                'header' => 'Number Of Orders',
                'type' => 'number',
            ),
            'sum_total_qty_ordered' => array(
                'header' => 'Items Ordered',
                'type' => 'number',
                'hidden' => true,
            ),
            'sum_grand_total' => array(
                'header' => 'Lifetime Sales',
                'type' => 'currency',
            ),
            'avg_grand_total' => array(
                'header' => 'Average Sale',
                'type' => 'currency',
            ),
        );

        if (Mage::getSingleton('advr/config')->isDisplayCustomerAddresses()) {
            $addressAttributes = Mage::getSingleton('advr/system_config_source_customerAddressAttribute')->toOptionHash();
            foreach ($addressAttributes as $attrCode => $attrLabel) {
                $columns['customer_address_'.$attrCode] = array(
                    'header' => 'Address / '.$attrLabel,
                    'type' => 'text',
                    'hidden' => true,
                );
            }
        }

        return $columns;
    }

    public function productFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $this->getCollection()->getSelect()->where("sales_order_item_subquery_table.products like ?", "%$value%");
        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function products($value, $row, $column)
    {
        $data = array();

        $products = $row->getData('products');
        $products = explode('@', $products);

        $aggregated = array();

        $deleteProducts = 0;
        foreach ($products as $product) {

            if ('' === $product) {
                return '';
            }

            $row = explode('^', $product);

            if (isset($row[0])) {
                if ('deleted' !== $row[0]) {
                    $id = (int)$row[0];
                }

                $type = isset($row[1]) ? $row[1] : '';
                $name = isset($row[2]) ? $row[2] : '';
                $sku = isset($row[3]) ? $row[3] : '';
                $qty = isset($row[4]) ? (int)$row[4] : 0;
                $parent = isset($row[5]) ? (int)$row[5] : null;
            }

            if (isset($id)) {
                if (!isset($aggregated[$id])) {
                    $aggregated[$id] = array('name' => $name, 'qty' => $qty, 'sku' => $sku, 'type' => $type, 'parent' => $parent);
                } else {
                    $aggregated[$id]['qty'] += $qty;
                }
            } else {
                $aggregated["deleted_" . $deleteProducts] = array('name' => $name, 'qty' => $qty, 'sku' => $sku, 'type' => $type, 'parent' => $parent);
                $deleteProducts++;
            }
        }

        foreach ($aggregated as $id => $item) {
            $d = $item['sku']
                . ' / '
                . Mage::helper('core/string')->truncate($item['name'], 50)
                . ' / ' . intval($item['qty'])
                . ' / ' . $item['type'];

            if( is_numeric($id) ) {
                $url = $this->getUrl('adminhtml/catalog_product/edit', array('id' => $id));
                $d = '<a class="nobr" href="' . $url . '">'
                    . $d
                    . '</a>';
            }

            $data[] = $d;
        }

        return implode('<br>', $data);
    }

    public function customerCallBack($row)
    {
        if ($row->getCustomerId()) {
            return Mage::helper('adminhtml')->getUrl('adminhtml/customer/edit', array('id' => $row->getCustomerId()));
        }

        return false;
    }
}
