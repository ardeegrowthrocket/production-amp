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



class Mirasvit_Advr_Model_Report_Table_View implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    const TABLE_NAME = 'reports_viewed_product_index_table';

    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        if (!$collection->isJoined(self::TABLE_NAME)) {
            $expr = '(SELECT product_id, COUNT(DISTINCT(visitor_id)) as qtyVisited, COUNT(DISTINCT(customer_id))'
                . '  as qtyCustomerVisited FROM '.$collection->getTable('reports/viewed_product_index');

            if (isset($data['from']) || isset($data['to'])) {
                $expr .= " WHERE ";
                if (isset($data['from'])) {
                    $expr .= " added_at >= '" .$data['from']."'";;
                    $from = true;
                }

                if (isset($data['to'])) {
                    if ($from) {
                        $expr .= " AND ";
                    }
                    $expr .= " added_at < '" .$data['to']."'";;
                }

                $expr .= " GROUP BY product_id)";
            }

            $collection->getSelect()->joinLeft(
                array(self::TABLE_NAME => new Zend_Db_Expr($expr),),
                self::TABLE_NAME.'.product_id = catalog_product_table.entity_id',
                array('reports_viewed_product_index_table.qtyVisited',
                    'reports_viewed_product_index_table.qtyCustomerVisited',
                    'cr' =>'ROUND(COUNT(DISTINCT(sales_order_table.entity_id)) / '
                        . '(reports_viewed_product_index_table.qtyVisited + '
                        . 'reports_viewed_product_index_table.qtyCustomerVisited) * 100)'
                )
            );

            $collection->setIsJoined(self::TABLE_NAME);
        }

        return $collection;
    }
}
