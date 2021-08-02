<?php

class Aitoc_Aitcbp_Model_Mysql4_Rewrite_Bundle_Indexer_Price
    extends Mage_Bundle_Model_Mysql4_Indexer_Price
{

    protected function _prepareBundlePriceByType($priceType, $entityIds = null)
    {
        $write = $this->_getWriteAdapter();
        $table = $this->_getBundlePriceTable();

        $select = $write->select()
            ->from(array('e' => $this->getTable('catalog/product')), array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                '',
                array('customer_group_id'));
        $this->_addWebsiteJoinToSelect($select, true);
        $this->_addProductWebsiteJoinToSelect($select, 'cw.website_id', 'e.entity_id');
        $select->columns('website_id', 'cw')
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->joinLeft(
                array('tp' => $this->_getTierPriceIndexTable()),
                'tp.entity_id = e.entity_id AND tp.website_id = cw.website_id'
                    . ' AND tp.customer_group_id = cg.customer_group_id',
                array())
            ->where('e.type_id=?', $this->getTypeId());
			
		if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{		
		    $select->joinLeft(
                array('gp' => $this->_getGroupPriceIndexTable()),
                'gp.entity_id = e.entity_id AND gp.website_id = cw.website_id'
                    . ' AND gp.customer_group_id = cg.customer_group_id',
                array()
            );
		}	
		
        // add enable products limitation
        $statusCond = $write->quoteInto('=?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->_addAttributeToSelect($select, 'status', 'e.entity_id', 'cs.store_id', $statusCond, true);

        if (Mage::helper('core')->isModuleEnabled('Mage_Tax')) {
        $taxClassId = $this->_addAttributeToSelect($select, 'tax_class_id', 'e.entity_id', 'cs.store_id');
        } else {
            $taxClassId = new Zend_Db_Expr('0');
        }
		
		if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{
            if ($priceType == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                $select->columns(array('tax_class_id' => new Zend_Db_Expr('0')));
            } else {
                $select->columns(
                    array('tax_class_id' => $write->getCheckSql($taxClassId . ' IS NOT NULL', $taxClassId, 0))
                );
            }
        }
		else
		{
		    $select->columns(array('tax_class_id' => new Zend_Db_Expr("IF($taxClassId IS NOT NULL, $taxClassId, 0)")));
		}


        $priceTypeCond = $write->quoteInto('=?', $priceType);
        $this->_addAttributeToSelect($select, 'price_type', 'e.entity_id', 'cs.store_id', $priceTypeCond);

        $cbpGroup       = $this->_addAttributeToSelect($select, 'cbp_group', 'e.entity_id', 'cs.store_id'); 
        
        $select->joinLeft(
            array('cbpg' => $this->getTable('aitcbp/group')),
            "cbpg.entity_id = {$cbpGroup}",
            array() );
        
        $select->joinLeft(
            array('cbpppi' => $this->getTable('aitcbp/product_price_index')),
            "cbpppi.product_id = e.entity_id AND cbpppi.website_id = cw.website_id",
            array() );
         
        $price              = $this->_addAttributeToSelect($select, 'price', 'e.entity_id', 'cs.store_id');
        $isModuleEnabled    = $this->_addConfigDataToSelect($select, Aitoc_Aitcbp_Helper_Data::XML_CONFIG_IS_ENABLED, 'cs.website_id', 'cs.store_id');                                           
        $newPrice           = new Zend_Db_Expr("IF(({$isModuleEnabled} = 1) AND (IFNULL({$cbpGroup}, 0) <> 0) AND (cbpg.is_active = 1) AND (cbpppi.price IS NOT NULL), cbpppi.price, {$price})");                                            

        $specialPrice   = $this->_addAttributeToSelect($select, 'special_price', 'e.entity_id', 'cs.store_id');
        $specialFrom    = $this->_addAttributeToSelect($select, 'special_from_date', 'e.entity_id', 'cs.store_id');
        $specialTo      = $this->_addAttributeToSelect($select, 'special_to_date', 'e.entity_id', 'cs.store_id');
        
        if(Aitoc_Aitsys_Abstract_Service::get()->isMagentoVersion('>=1.6'))
            $currentDate = $curentDate     = new Zend_Db_Expr('cwd.website_date');
        else
            $curentDate     = new Zend_Db_Expr('cwd.date');

		if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{
		    $specialExpr    = $write->getCheckSql(
            $write->getCheckSql(
                $specialFrom . ' IS NULL',
                '1',
                $write->getCheckSql(
                    $specialFrom . ' <= ' . $curentDate,
                    '1',
                    '0'
                )
            ) . " > 0 AND ".
            $write->getCheckSql(
                $specialTo . ' IS NULL',
                '1',
                $write->getCheckSql(
                    $specialTo . ' >= ' . $curentDate,
                    '1',
                    '0'
                )
            )
            . " > 0 AND {$specialPrice} > 0 AND {$specialPrice} < 100 ",
            $specialPrice,
            '0'
            );

            $groupPriceExpr = $write->getCheckSql(
                'gp.price IS NOT NULL AND gp.price > 0 AND gp.price < 100',
                'gp.price',
                '0'
            );
		}
		else
		{
        $specialExpr    = new Zend_Db_Expr("IF(IF({$specialFrom} IS NULL, 1, "
            . "IF({$specialFrom} <= {$curentDate}, 1, 0)) > 0 AND IF({$specialTo} IS NULL, 1, "
            . "IF({$specialTo} >= {$curentDate}, 1, 0)) > 0 AND {$specialPrice} > 0, $specialPrice, 0)");
		}
        $tierExpr       = new Zend_Db_Expr("tp.min_price");

		if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{
		    if ($priceType == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                $finalPrice = $write->getCheckSql(
                    $specialExpr . ' > 0',
                    'ROUND(' . $newPrice . ' * (' . $specialExpr . '  / 100), 4)',
                    $newPrice
                );
                $tierPrice = $write->getCheckSql(
                    $tierExpr . ' IS NOT NULL',
                    'ROUND(' . $newPrice . ' - ' . '(' . $newPrice . ' * (' . $tierExpr . ' / 100)), 4)',
                    'NULL'
                );
                $groupPrice = $write->getCheckSql(
                    $groupPriceExpr . ' > 0',
                    'ROUND(' . $newPrice . ' - ' . '(' . $newPrice . ' * (' . $groupPriceExpr . ' / 100)), 4)',
                    'NULL'
                );
                $finalPrice = $write->getCheckSql(
                    "{$groupPrice} IS NOT NULL AND {$groupPrice} < {$finalPrice}",
                    $groupPrice,
                    $finalPrice
                );
            } else {
                $finalPrice     = new Zend_Db_Expr("0");
                $tierPrice      = $write->getCheckSql($tierExpr . ' IS NOT NULL', '0', 'NULL');
                $groupPrice     = $write->getCheckSql($groupPriceExpr . ' > 0', $groupPriceExpr, 'NULL');
            }
			
	        $select->columns(array(
            'price_type'          => new Zend_Db_Expr($priceType),
            'special_price'       => $specialExpr,
            'tier_percent'        => $tierExpr,
            'orig_price'          => $write->getCheckSql($price . ' IS NULL', '0', $price),
            'price'               => $finalPrice,
            'min_price'           => $finalPrice,
            'max_price'           => $finalPrice,
            'tier_price'          => $tierPrice,
            'base_tier'           => $tierPrice,
            'group_price'         => $groupPrice,
            'base_group_price'    => $groupPrice,
            'group_price_percent' => new Zend_Db_Expr('gp.price'),
            ));
		}
		else
		{		
            if ($priceType == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                $finalPrice  = new Zend_Db_Expr("IF({$specialExpr} > 0,"
                    . " ROUND({$newPrice} * ({$specialExpr} / 100), 4), {$newPrice})");
                $tierPrice      = new Zend_Db_Expr("IF({$tierExpr} IS NOT NULL,"
                    . " ROUND({$newPrice} - ({$newPrice} * ({$tierExpr} / 100)), 4), NULL)");
            } else {
                $finalPrice     = new Zend_Db_Expr("0");
                $tierPrice      = new Zend_Db_Expr("IF({$tierExpr} IS NOT NULL, 0, NULL)");
            }
		
            $select->columns(array(
            'price_type'    => new Zend_Db_Expr($priceType),
            'special_price' => $specialExpr,
            'tier_percent'  => $tierExpr,
            'orig_price'    => new Zend_Db_Expr("IF({$price} IS NULL, 0, {$price})"),
            'price'         => $finalPrice,
            'min_price'     => $finalPrice,
            'max_price'     => $finalPrice,
            'tier_price'    => $tierPrice,
            'base_tier'     => $tierPrice,
            ));
        }
		
        if (!is_null($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }
        
        /**
         * Add additional external limitation
         */
        Mage::dispatchEvent('catalog_product_prepare_index_select', array(
            'select'        => $select,
            'entity_field'  => new Zend_Db_Expr('e.entity_id'),
            'website_field' => new Zend_Db_Expr('cw.website_id'),
            'store_field'   => new Zend_Db_Expr('cs.store_id')
        ));

        if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{
            $query = $select->insertFromSelect($table, array(), false);
        }
		else
		{
            $query = $select->insertFromSelect($table); //echo '1'.$query; exit;
		}
        $write->query($query);

        return $this;
    }
    
    protected function _addConfigDataToSelect($select, $path, $website, $store)
    {
        $select
            ->joinLeft(
                array('default' => $this->getTable('core/config_data')),
                "default.path = '{$path}' AND default.scope = 'default'",
                array())
            ->joinLeft(
                array('websites' => $this->getTable('core/config_data')),
                "websites.path = '{$path}' AND websites.scope = 'websites' AND websites.scope_id = {$website}",
                array())
            ->joinLeft(
                array('stores' => $this->getTable('core/config_data')),
                "stores.path = '{$path}' AND stores.scope = 'stores' AND stores.scope_id = {$store}",
                array());
        return new Zend_Db_Expr("IFNULL(stores.value, IFNULL(websites.value, IFNULL(default.value, 1)))");
    }
}
