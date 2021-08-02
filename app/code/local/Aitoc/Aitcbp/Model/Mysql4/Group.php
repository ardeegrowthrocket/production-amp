<?php

class Aitoc_Aitcbp_Model_Mysql4_Group extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('aitcbp/group', 'entity_id');
    }

    protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {

        if ($object->getId()) {
            // Unassign all assigned products
            $collection = Mage::getModel('catalog/product')->getCollection();
            /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            $collection->addAttributeToFilter('cbp_group', $object->getId());
            $code = 'cbp_group';
            foreach ($collection as $product) {
                /* @var $product Mage_Catalog_Model_Product */
                $product->setData($code, 0);
                $product->getResource()->saveAttribute($product, $code);
            }

            // Remove group from rules
            $collection = Mage::getModel('aitcbp/rule')->getCollection();
            /* @var $collection Aitoc_Aitcbp_Model_Mysql4_Rule_Collection */
            $collection->getSelect()->where('FIND_IN_SET(?, in_groups)', $object->getId());
            foreach ($collection as $rule) {
                $rule->load($rule->getId());
                $inGroups = explode(',', $rule->getInGroups());
                $index    = array_search($object->getId(), $inGroups);
                if (is_null($index)) {
                    continue;
                }
                unset($inGroups[$index]);
                $rule->setInGroups($inGroups);
                $rule->save();
            }
        }

        $val = Mage::getConfig()->getNode('modules/Aitoc_Aitpagecache/active');
        if ((string)$val == 'true') {
            Mage::helper('aitpagecache')->clearCache();
        }

        return parent::_afterDelete($object);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getInGroupProducts() && $object->getId()) {
            $params          = explode('&', $object->getInGroupProducts());
            $productIdsSet   = array();
            $productIdsUnset = array();
            foreach ($params as $param) {
                list($key, $value) = explode('=', $param);
                if ($key == 'on') {
                    continue;
                }
                if ($value == 1) {
                    $productIdsSet[] = $key;
                } elseif ($value == 0) {
                    $productIdsUnset[] = $key;
                } else {
                    continue;
                }
            }

            if (count($productIdsSet)) {
                $attrData['cbp_group'] = $object->getId();
                foreach (Mage::app()->getStores(true) as $store) {
                    Mage::getSingleton('catalog/product_action')
                        ->updateAttributes($productIdsSet, $attrData, $store->getId);
                }
            }
            if (count($productIdsUnset)) {
                $attrData['cbp_group'] = 0;
                foreach (Mage::app()->getStores(true) as $store) {
                    Mage::getSingleton('catalog/product_action')
                        ->updateAttributes($productIdsUnset, $attrData, $store->getId);
                }
                Mage::getModel('aitcbp/product_price_index')->reindexEntity($productIdsUnset);

                Mage::getResourceSingleton('catalogrule/rule')
                    ->applyAllRulesForDateRange();

                $event = Mage::getSingleton('index/event')
                    ->setNewData(array('reindex_price_product_ids' => $productIdsUnset));
                Mage::getResourceSingleton('catalog/product_indexer_price')
                    ->aitocCatalogProductMassAction($event);
            }
        }
        $productIds = $object->getAssociatedProducts();

        if (count($productIds)) {
            Mage::getModel('aitcbp/product_price_index')->reindexEntity($productIds);

            Mage::getResourceSingleton('catalogrule/rule')
                ->applyAllRulesForDateRange();
            $event = Mage::getSingleton('index/event')
                ->setNewData(array('reindex_price_product_ids' => $productIds));

            Mage::getResourceSingleton('catalog/product_indexer_price')
                ->aitocCatalogProductMassAction($event);
        }
        if (!is_null(Aitoc_Aitsys_Abstract_Service::get()->platform()->getModule('Aitoc_Aitpagecache'))) {
            $val = Mage::getConfig()->getNode('modules/Aitoc_Aitpagecache/active');
            if ((string)$val == 'true') {
                Mage::helper('aitpagecache')->clearCache();
            }
        }

        return parent::_afterSave($object);
    }

    public function getAssociatedProductIds(Mage_Core_Model_Abstract $object)
    {
        $read   = $this->_getReadAdapter();
        $select = $read->select()
            ->from(
                array('e' => $this->getTable('catalog/product')),
                array('entity_id')
            )
            ->join(
                array('cw' => $this->getTable('core/website')),
                '',
                array()
            )
            ->join(
                array('csg' => $this->getTable('core/store_group')),
                'csg.website_id = cw.website_id AND cw.default_group_id = csg.group_id',
                array()
            )
            ->join(
                array('cs' => $this->getTable('core/store')),
                'csg.default_store_id = cs.store_id AND cs.store_id != 0',
                array()
            );

        $group = $this->_addAttributeToSelect($select, 'cbp_group', 'e.entity_id', 'cs.store_id', '<>0');

        $select->where("{$group}=? ", $object->getId());

        $result = $read->fetchCol($select);

        return $result;
    }

    protected function _addAttributeToSelect($select, $attrCode, $entity, $store, $condition = null, $required = false)
    {
        $attribute      = $this->_getAttribute($attrCode);
        $attributeId    = $attribute->getAttributeId();
        $attributeTable = $attribute->getBackend()->getTable();
        $joinType       = !is_null($condition) || $required ? 'join' : 'joinLeft';

        if ($attribute->isScopeGlobal()) {
            $alias = 'ta_' . $attrCode;
            $select->$joinType(
                array($alias => $attributeTable),
                "{$alias}.entity_id = {$entity} AND {$alias}.attribute_id = {$attributeId}",
                array()
            );
            $expression = new Zend_Db_Expr("{$alias}.value");
        } else {
            $dAlias = 'tad_' . $attrCode;
            $sAlias = 'tas_' . $attrCode;

            $select->$joinType(
                array($dAlias => $attributeTable),
                "{$dAlias}.entity_id = {$entity} AND {$dAlias}.attribute_id = {$attributeId}",
                array()
            );
            $select->joinLeft(
                array($sAlias => $attributeTable),
                "{$sAlias}.entity_id = {$entity} AND {$sAlias}.attribute_id = {$attributeId}"
                . " AND {$sAlias}.store_id = {$store}",
                array()
            );
            $expression = new Zend_Db_Expr("IF({$sAlias}.value_id > 0, {$sAlias}.value, {$dAlias}.value)");
        }

        if (!is_null($condition)) {
            $select->where("{$expression}{$condition}");
        }

        return $expression;
    }

    protected function _getAttribute($attributeCode)
    {
        return Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
    }

}
