<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */


abstract class Amasty_Audit_Block_Adminhtml_DefaultLog extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('date_time');
        $this->setId('audit_history');
    }

    public function showFullName($value, $row, $column)
    {
        $username = $row->getUsername();
        if ($username) {
            $user = Mage::getModel('admin/user')->loadByUsername($username);

            return $user->getName();
        }

        return '';
    }

    public function decorateStatus($value, $row, $column)
    {
        return '<span class="amaudit-' . $value . '">' . $value . '</span>';
    }


    public function showOpenElementUrl($value, $row, $column)
    {
        $category = $row->getCategory();
        $url = '';

        if ($category) {
            list(, $controller) = explode('/', $category);

            if (!is_null($controller)) {
                if ('back' == $row->getParametrName()
                    || 'underfined' == $row->getParametrName()
                ) {
                    switch ($controller) {
                        case 'catalog_product_attribute':
                            $param = 'attribute_id';
                            break;
                        default:
                            $param = 'id';
                    }
                } else {
                    $param = $row->getParametrName();
                }

                $controllers = array(
                    'catalog_product',
                    'customer',
                    'customer_group',
                    'catalog_product_attribute'
                );
                if ($row->getElementId()
                    && $category
                    && 'Delete' != $row->getType()
                    && in_array($controller, $controllers)
                ) {
                    $url = $this->getUrl('adminhtml/' . $controller . '/edit', array($param => $row->getElementId()));
                }
            }
        }

        $info = $row->getInfo();
        if (false !== strpos($info, 'Order ID')) {
            $url = $this->getUrl('adminhtml/sales_order/view', array('order_id' => preg_replace("/[^0-9]/", '', $info)));
        }

        $view = '';
        if ($url) {
            $view = '&nbsp;<a href="' . $url . '"><span>[' . Mage::helper('amaudit')->__('view') . ']</span></a>';
        }

        return '<span>' . $value . '</span>' . $view;
    }

    public function showActions($value, $row, $column)
    {
        $preview = '';
        $rowTypes = array(
            'Edit',
            'New',
            'Restore'
        );
        if (in_array($row->getType(), $rowTypes)
            && null != $row->is_logged
        ) {
            $preview = '<a class="amaudit-preview" id="' . $row->getId() . '" onclick="buble.showToolTip(this); return false">' . Mage::helper('amaudit')->__('Preview Changes') . '</a><br>';
        }

        return $preview . '<a href="' . $this->getUrl('adminhtml/amaudit_log/edit', array('id' => $row->getId())) . '"><span>' . Mage::helper('amaudit')->__('View Details') . '</span></a>';
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/amaudit_log/edit', array('id' => $row->getId()));
    }
}
