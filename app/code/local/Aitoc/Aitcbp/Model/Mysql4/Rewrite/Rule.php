<?php
/**
 * @copyright  Copyright (c) 2010 AITOC, Inc. 
 */

class Aitoc_Aitcbp_Model_Mysql4_Rewrite_Rule extends Mage_CatalogRule_Model_Mysql4_Rule
{
    
    protected function _getRuleProductsStmt($fromDate, $toDate, $productId=null, $websiteId = null)
    {
        $read = $this->_getReadAdapter();
        /**
         * Sort order is important
         * It used for check stop price rule condition.
         * website_id   customer_group_id   product_id  sort_order
         *  1           1                   1           0
         *  1           1                   1           1
         *  1           1                   1           2
         * if row with sort order 1 will have stop flag we should exclude
         * all next rows for same product id from price calculation
         */
        $select = $read->select()
            ->from(array('rp'=>$this->getTable('catalogrule/rule_product')))
            ->where($read->quoteInto('rp.from_time=0 or rp.from_time<=?', $toDate)
            ." or ".$read->quoteInto('rp.to_time=0 or rp.to_time>=?', $fromDate))
            ->order(array('rp.website_id', 'rp.customer_group_id', 'rp.product_id', 'rp.sort_order'));

        if (!is_null($productId)) {
            $select->where('rp.product_id=?', $productId);
        }

        /**
         * Join default price and websites prices to result
         */
        $priceAttr  = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'price');
        $priceTable = $priceAttr->getBackend()->getTable();
        $attributeId= $priceAttr->getId();

        $joinCondition = '%1$s.entity_id=rp.product_id AND (%1$s.attribute_id='.$attributeId.') and %1$s.store_id=%2$s';

        $defaultPrice = new Zend_Db_Expr("IF((cbpg.is_active = 1), IFNULL(cbpppi.price, pp_default.value), pp_default.value)");
        
        $isModuleEnabled = $this->_addConfigDataToSelect(
            $select, '', Aitoc_Aitcbp_Helper_Data::XML_CONFIG_IS_ENABLED, 'store', Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID)->getWebsiteId(), Mage_Core_Model_App::ADMIN_STORE_ID);

        $select->join(
            array('pp_default'=>$priceTable),
            sprintf($joinCondition, 'pp_default', Mage_Core_Model_App::ADMIN_STORE_ID),
            array('default_price'=>"IF(({$isModuleEnabled} = 1), {$defaultPrice}, pp_default.value)")
        );
        
        $select->joinLeft(
            array('cbpppi' => $this->getTable('aitcbp/product_price_index')),
            "cbpppi.product_id = rp.product_id AND cbpppi.website_id = ".Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID)->getWebsiteId(),
            array() 
        );
        
        $select->joinLeft(
            array('cbpg' => $this->getTable('aitcbp/group')),
            "cbpg.entity_id = cbpppi.cbp_group",
            array() 
        );
        
        if ($websiteId !== null) {
            $website  = Mage::app()->getWebsite($websiteId);
            $defaultGroup = $website->getDefaultGroup();
            if ($defaultGroup instanceof Mage_Core_Model_Store_Group) {
                $storeId    = $defaultGroup->getDefaultStoreId();
            } else {
                $storeId    = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $select->joinInner(
                array('product_website'=>$this->getTable('catalog/product_website')),
                'product_website.product_id=rp.product_id AND rp.website_id=product_website.website_id AND product_website.website_id='.$websiteId,
                array()
            );
            
            $isModuleEnabled = $this->_addConfigDataToSelect(
                $select, $websiteId, Aitoc_Aitcbp_Helper_Data::XML_CONFIG_IS_ENABLED, 'website', $websiteId, $storeId);
            
            $cbpIndexTableAlias = 'cbpppi'.$websiteId;
            $cpbGroupTableAlias = 'cbpg'.$websiteId;
            $select->joinLeft(
                array($cbpIndexTableAlias => $this->getTable('aitcbp/product_price_index')),
                "{$cbpIndexTableAlias}.product_id = rp.product_id AND {$cbpIndexTableAlias}.website_id = ".$websiteId,
                array() 
            );
            
            $select->joinLeft(
                array($cpbGroupTableAlias => $this->getTable('aitcbp/group')),
                "{$cpbGroupTableAlias}.entity_id = {$cbpIndexTableAlias}.cbp_group",
                array() 
            );
            
            $tableAlias = 'pp'.$websiteId;
            $fieldAlias = 'website_'.$websiteId.'_price';
            
            $currentPrice = new Zend_Db_Expr("IFNULL({$tableAlias}.value, pp_default.value)");                                                                         
            $websitePrice = new Zend_Db_Expr("IF(({$cpbGroupTableAlias}.is_active = 1), IFNULL({$cbpIndexTableAlias}.price, {$currentPrice}), {$currentPrice})");
            
            $select->joinLeft(
                array($tableAlias=>$priceTable),
                sprintf($joinCondition, $tableAlias, $storeId),         
                array($fieldAlias=>"IF(({$isModuleEnabled} = 1), {$websitePrice}, {$currentPrice})")
            );
        } else {
            foreach (Mage::app()->getWebsites() as $website) {
                $websiteId  = $website->getId();
                $defaultGroup = $website->getDefaultGroup();
                if ($defaultGroup instanceof Mage_Core_Model_Store_Group) {
                    $storeId    = $defaultGroup->getDefaultStoreId();
                } else {
                    $storeId    = Mage_Core_Model_App::ADMIN_STORE_ID;
                }

                $cbpIndexTableAlias = 'cbpppi'.$websiteId;
                $cpbGroupTableAlias = 'cbpg'.$websiteId;
                $select->joinLeft(
                    array($cbpIndexTableAlias => $this->getTable('aitcbp/product_price_index')),
                    "{$cbpIndexTableAlias}.product_id = rp.product_id AND {$cbpIndexTableAlias}.website_id = ".$websiteId,
                    array() 
                );
                
                $select->joinLeft(
                    array($cpbGroupTableAlias => $this->getTable('aitcbp/group')),
                    "{$cpbGroupTableAlias}.entity_id = {$cbpIndexTableAlias}.cbp_group",
                    array() 
                );
                
                $isModuleEnabled = $this->_addConfigDataToSelect(
                    $select, $websiteId, Aitoc_Aitcbp_Helper_Data::XML_CONFIG_IS_ENABLED, 'website', $websiteId, $storeId);
            
                $tableAlias = 'pp'.$websiteId;
                $fieldAlias = 'website_'.$websiteId.'_price';
                
                $currentPrice = new Zend_Db_Expr("IFNULL({$tableAlias}.value, pp_default.value)");   
                
                $websitePrice = new Zend_Db_Expr("IF(({$cpbGroupTableAlias}.is_active = 1), IFNULL({$cbpIndexTableAlias}.price, {$currentPrice}), {$currentPrice})");
             
                $select->joinLeft(
                    array($tableAlias=>$priceTable),
                    sprintf($joinCondition, $tableAlias, $storeId),
                    array($fieldAlias=>"IF(({$isModuleEnabled} = 1), {$websitePrice}, {$currentPrice})")
                );
            }
        }
        return $read->query($select);
    }

    protected function _addConfigDataToSelect($select, $aliasId, $path, $scope, $website, $store)
    {
        $table = $this->getTable('core/'.$scope);
        $tableAlias = 'c'.$aliasId;
        $defaultAlias = 'default'.$aliasId;
        $websitesAlias = 'websites'.$aliasId;
        $storesAlias = 'stores'.$aliasId;
        $select
            ->join(
                array($tableAlias => $table),
                "{$tableAlias}.{$scope}_id = {$$scope}",
                array())
            ->joinLeft(
                array($defaultAlias => $this->getTable('core/config_data')),
                "{$defaultAlias}.path = '{$path}' AND {$defaultAlias}.scope = 'default'",
                array())
            ->joinLeft(
                array($websitesAlias => $this->getTable('core/config_data')),
                "{$websitesAlias}.path = '{$path}' AND {$websitesAlias}.scope = 'websites' AND {$websitesAlias}.scope_id = {$tableAlias}.website_id",
                array());
        if ('store' == $scope)
        {
            $select->joinLeft(
                array($storesAlias => $this->getTable('core/config_data')),
                "{$storesAlias}.path = '{$path}' AND {$storesAlias}.scope = 'stores' AND {$storesAlias}.scope_id = {$tableAlias}.store_id",
                array());
            return new Zend_Db_Expr("IFNULL({$storesAlias}.value, IFNULL({$websitesAlias}.value, IFNULL({$defaultAlias}.value, 1)))");                   
        }
           
        return new Zend_Db_Expr("IFNULL({$websitesAlias}.value, IFNULL({$defaultAlias}.value, 1))");
    }
    
    protected function _getAttribute($attributeCode)
    {
        return Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
    }

    protected function _addAttributeToSelect($select, $aliasCode, $attrCode, $entity, $store, $condition = null, $required = false)
    {
        $attribute      = $this->_getAttribute($attrCode);
        $attributeId    = $attribute->getAttributeId();
        $attributeTable = $attribute->getBackend()->getTable();
        $joinType       = !is_null($condition) || $required ? 'join' : 'joinLeft';

        if ($attribute->isScopeGlobal()) {
            $alias = 'ta_' . $attrCode . $aliasCode;
            $select->$joinType(
                array($alias => $attributeTable),
                "{$alias}.entity_id = {$entity} AND {$alias}.attribute_id = {$attributeId}"
                    . " AND {$alias}.store_id = 0",
                array()
            );
            $expression = new Zend_Db_Expr("{$alias}.value");
        } else {
            $dAlias = 'tad_' . $attrCode . $aliasCode;
            $sAlias = 'tas_' . $attrCode . $aliasCode;

            $select->$joinType(
                array($dAlias => $attributeTable),
                "{$dAlias}.entity_id = {$entity} AND {$dAlias}.attribute_id = {$attributeId}"
                    . " AND {$dAlias}.store_id = 0",
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
}
