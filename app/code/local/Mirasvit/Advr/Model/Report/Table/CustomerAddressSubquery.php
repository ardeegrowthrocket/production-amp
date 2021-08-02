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



class Mirasvit_Advr_Model_Report_Table_CustomerAddressSubquery implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    const TABLE_NAME = 'customer_address_entity_table';

    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        if (!$collection->isJoined(self::TABLE_NAME)) {
            $addressTable = $collection->getTable('customer/address_entity');
            $collection->getSelect()->joinLeft(
                array(self::TABLE_NAME => new Zend_Db_Expr(
                    "(SELECT GROUP_CONCAT(entity_id) as entity_id, GROUP_CONCAT(parent_id) as parent_id "
                        . "FROM {$addressTable} GROUP BY parent_id)"
                )),
                'customer_entity_table.entity_id IN ('.self::TABLE_NAME.'.parent_id)',
                array()
            );

            $collection->setIsJoined(self::TABLE_NAME);
        }

        return $collection;
    }
}
