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



class Mirasvit_Advr_Model_Report_Condition_OrderItem implements Mirasvit_Advr_Model_Report_Condition_ConditionInterface
{
    /**
     * @inheritdoc
     */
    public function getConditions(Mirasvit_Advr_Model_Report_Abstract $report, array $conditions = [])
    {
        if ($report->getFilterData()->getIncludeChild()) {
            $report->getSelect()
                ->joinLeft(
                    array('sales_order_item_parent_table' => $report->getTable('sales/order_item')),
                    'sales_order_item_parent_table.product_id = catalog_product_table.entity_id
                        AND (sales_order_item_parent_table.parent_item_id IS NOT NULL
                            OR sales_order_item_parent_table.product_type IN ("simple", "virtual", "downloadable") )',
                    array()
                );

            $conditions[] = 'sales_order_item_table.item_id =
                IFNULL(sales_order_item_parent_table.parent_item_id, sales_order_item_parent_table.item_id)';
        } else {
            $conditions[] = 'sales_order_item_table.parent_item_id IS NULL';
        }

        return $conditions;
    }
}
