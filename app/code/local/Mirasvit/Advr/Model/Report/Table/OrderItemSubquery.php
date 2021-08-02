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



class Mirasvit_Advr_Model_Report_Table_OrderItemSubquery implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    const TABLE_NAME = 'sales_order_item_subquery_table';

    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        if (!$collection->isJoined(self::TABLE_NAME)) {
            $orderItemTable = $collection->getTable('sales/order_item');
            $collection->getSelect()->joinLeft(
                array(self::TABLE_NAME => new Zend_Db_Expr(
                    '(SELECT GROUP_CONCAT(CONCAT_WS("^", IFNULL(product_id, "deleted"), product_type, name, sku,'
                        . 'qty_ordered, parent_item_id) SEPARATOR "@") as products, order_id FROM '.$orderItemTable
                        . ' GROUP BY order_id)'
                )),
                'sales_order_table.entity_id = '.self::TABLE_NAME.'.order_id',
                array()
            );


            $collection->setIsJoined(self::TABLE_NAME);
        }

        return $collection;
    }
}
