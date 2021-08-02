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



class Mirasvit_Advr_Model_Report_Table_ShippingAddress implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    const TABLE_NAME = 'sales_order_shipping_address_table';

    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        if ($collection->isJoined(self::TABLE_NAME)) {
            return $collection;
        }

        //$collection->joinRelatedDependencies('sales/order');

        $collection->getSelect()->joinLeft(
            array(self::TABLE_NAME => $collection->getTable('sales/order_address')),
            self::TABLE_NAME.'.entity_id = sales_order_table.shipping_address_id',
            array()
        );

        $collection->setIsJoined(self::TABLE_NAME);

        return $collection;
    }
}