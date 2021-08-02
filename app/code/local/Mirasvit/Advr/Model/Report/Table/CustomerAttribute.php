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



class Mirasvit_Advr_Model_Report_Table_CustomerAttribute implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        $attrCode = $data['attribute'];
        $tableName = 'customer_'.$attrCode.'_table';

        if ($collection->isJoined($tableName)) {
            return $collection;
        }

        $attr = Mage::getSingleton('eav/config')->getAttribute('customer', $attrCode);

        $conditons = array(
            $tableName.'.entity_id = customer_entity_table.entity_id',
            $tableName.'.attribute_id = '.$attr->getAttributeId(),
        );

        $collection->getSelect()->joinLeft(
            array($tableName => $attr->getBackend()->getTable()),
            implode(' AND ', $conditons),
            array()
        );
        $collection->setIsJoined($tableName);

        return $collection;
    }
}
