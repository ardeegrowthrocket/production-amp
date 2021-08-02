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



class Mirasvit_Advr_Model_Report_Table_CategoryName implements Mirasvit_Advr_Model_Report_Table_JoinableInterface
{
    const TABLE_NAME = 'catalog_category_name_table';

    /**
     * @inheritdoc
     */
    public function join(Mirasvit_Advr_Model_Report_Abstract $collection, array $data = [])
    {
        if ($collection->isJoined(self::TABLE_NAME)) {
            return $collection;
        }

        $collection->joinRelatedDependencies('catalog/category');
        $category = Mage::getResourceSingleton('catalog/category');
        $attr = $category->getAttribute('name');;

        $collection->getSelect()->joinLeft(
            array(self::TABLE_NAME => $attr->getBackend()->getTable()),
            implode(' AND ', array(
                self::TABLE_NAME.'.entity_id = catalog_category_table.entity_id',
                self::TABLE_NAME.'.entity_type_id = '.$category->getTypeId(),
                self::TABLE_NAME.'.attribute_id = '.$attr->getAttributeId(),
                self::TABLE_NAME.'.store_id = 0',
            )),
            array()
        );
        //$collection->getJoinedTables[self::TABLE_NAME] = true;
        $collection->setIsJoined(self::TABLE_NAME);

        return $collection;
    }
}
