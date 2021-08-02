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



class Mirasvit_Advr_Model_Report_Table_ProductAttribute implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        $attrCode  = $data['attribute'];
        $tableName = $collection->prepareExpression('catalog_product_'.$attrCode.'_table');

        if (!$collection->isJoined($tableName)) {
            $collection->joinRelatedDependencies('sales/order_item');

            $product = Mage::getResourceSingleton('catalog/product');
            $attr = Mage::getSingleton('eav/config')->getAttribute($product->getTypeId(), $attrCode);

            $conditions = array();
            if ($collection->getFilterData()->getIncludeChild()) {
                $conditions[] = $tableName.'.entity_id = sales_order_item_parent_table.product_id';
            } elseif ($collection->isJoined('sales_order_item_parent_table')) {
                $conditions[] = $tableName.'.entity_id = IFNULL(sales_order_item_parent_table.product_id, '
                    . 'sales_order_item_table.product_id)';
            } else {
                $conditions[] = $tableName.'.entity_id = sales_order_item_table.product_id';
            }

            $conditions[] = $tableName.'.attribute_id = '.$attr->getAttributeId();
            $conditions[] = $tableName.'.entity_type_id = '.$product->getTypeId();
            $conditions[] = $tableName.'.store_id = 0';

            $collection->getSelect()->joinLeft(
                array($tableName => $attr->getBackend()->getTable()),
                implode(' AND ', $conditions),
                array()
            );
        }

        $collection->setIsJoined($tableName);

        return $collection;
    }
}
