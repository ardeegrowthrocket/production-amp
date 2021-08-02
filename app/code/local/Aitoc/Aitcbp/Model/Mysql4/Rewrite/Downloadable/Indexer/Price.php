<?php

class Aitoc_Aitcbp_Model_Mysql4_Rewrite_Downloadable_Indexer_Price
    extends Mage_Downloadable_Model_Mysql4_Indexer_Price
{
    
    protected function _prepareFinalPriceData($entityIds = null)
    {
        $this->_prepareDefaultFinalPriceTable();
       
        $write  = $this->_getWriteAdapter();
        $select = $write->select()
            ->from(array('e' => $this->getTable('catalog/product')), array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                '',
                array('customer_group_id'))
            ->join(
                array('cw' => $this->getTable('core/website')),
                '',
                array('website_id'))
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->join(
                array('csg' => $this->getTable('core/store_group')),
                'csg.website_id = cw.website_id AND cw.default_group_id = csg.group_id',
                array())
            ->join(
                array('cs' => $this->getTable('core/store')),
                'csg.default_store_id = cs.store_id AND cs.store_id != 0',
                array())
            ->join(
                array('pw' => $this->getTable('catalog/product_website')),
                'pw.product_id = e.entity_id AND pw.website_id = cw.website_id',
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
                array());
        }
			

        // add enable products limitation
        $statusCond = $write->quoteInto('=?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->_addAttributeToSelect($select, 'status', 'e.entity_id', 'cs.store_id', $statusCond, true);

        if (Mage::helper('core')->isModuleEnabled('Mage_Tax')) {
        $taxClassId = $this->_addAttributeToSelect($select, 'tax_class_id', 'e.entity_id', 'cs.store_id');
        } else {
            $taxClassId = new Zend_Db_Expr('0');
        }

        $select->columns(array('tax_class_id' => $taxClassId));

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
            $groupPrice     = $write->getCheckSql('gp.price IS NULL', "{$newPrice}", 'gp.price');
        
	        $specialFromDate    = $write->getDatePartSql($specialFrom);
            $specialToDate      = $write->getDatePartSql($specialTo);

            $specialFromUse     = $write->getCheckSql("{$specialFromDate} <= {$currentDate}", '1', '0');
            $specialToUse       = $write->getCheckSql("{$specialToDate} >= {$currentDate}", '1', '0');
            $specialFromHas     = $write->getCheckSql("{$specialFrom} IS NULL", '1', "{$specialFromUse}");
            $specialToHas       = $write->getCheckSql("{$specialTo} IS NULL", '1', "{$specialToUse}");
            $finalPrice         = $write->getCheckSql("{$specialFromHas} > 0 AND {$specialToHas} > 0"
               . " AND {$specialPrice} < {$newPrice}", $specialPrice, $newPrice);
            $finalPrice         = $write->getCheckSql("{$groupPrice} < {$finalPrice}", $groupPrice, $finalPrice);
        }
		else
		{
		    $finalPrice     = new Zend_Db_Expr("IF(IF({$specialFrom} IS NULL, 1, "
            . "IF(DATE({$specialFrom}) <= {$curentDate}, 1, 0)) > 0 AND IF({$specialTo} IS NULL, 1, "
            . "IF(DATE({$specialTo}) >= {$curentDate}, 1, 0)) > 0 AND {$specialPrice} < {$newPrice}, "
            . "{$specialPrice}, {$newPrice})");
		}
            
        $select->columns(array(
            'orig_price'    => $price,
            'price'         => $finalPrice,
            'min_price'     => $finalPrice,
            'max_price'     => $finalPrice,
            'tier_price'    => new Zend_Db_Expr('tp.min_price'),
            'base_tier'     => new Zend_Db_Expr('tp.min_price'),
        ));
		
		if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{
		    $select->columns(array(
			            'group_price'      => new Zend_Db_Expr('gp.price'),
                        'base_group_price' => new Zend_Db_Expr('gp.price'),
        ));
		}
		
		if (!is_null($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        /**
         * Add additional external limitation
         */
        Mage::dispatchEvent('prepare_catalog_product_index_select', array(
            'select'        => $select,
            'entity_field'  => new Zend_Db_Expr('e.entity_id'),
            'website_field' => new Zend_Db_Expr('cw.website_id'),
            'store_field'   => new Zend_Db_Expr('cs.store_id')
        ));

		if(version_compare(Mage::getVersion(), '1.7.0.0', '>='))
		{
            $query = $select->insertFromSelect($this->_getDefaultFinalPriceTable(), array(), false);
        }
		else
		{
            $query = $select->insertFromSelect($this->_getDefaultFinalPriceTable()); //echo '1'.$query; exit;
		}
        $write->query($query);
        
        /**
         * Add possibility modify prices from external events
         */
        $select = $write->select()
            ->join(array('wd' => $this->_getWebsiteDateTable()),
                'i.website_id = wd.website_id',
                array());
                
        if(Aitoc_Aitsys_Abstract_Service::get()->isMagentoVersion('>=1.6'))        
        {
            Mage::dispatchEvent('prepare_catalog_product_price_index_table', array(
                'index_table'       => array('i' => $this->_getDefaultFinalPriceTable()),
                'select'            => $select,
                'entity_id'         => 'i.entity_id',
                'customer_group_id' => 'i.customer_group_id',
                'website_id'        => 'i.website_id',
                'website_date'      => 'wd.website_date',
                'update_fields'     => array('price', 'min_price', 'max_price')
            ));
        } else {
            Mage::dispatchEvent('prepare_catalog_product_price_index_table', array(
                'index_table'       => array('i' => $this->_getDefaultFinalPriceTable()),
                'select'            => $select,
                'entity_id'         => 'i.entity_id',
                'customer_group_id' => 'i.customer_group_id',
                'website_id'        => 'i.website_id',
                'website_date'      => 'wd.date',
                'update_fields'     => array('price', 'min_price', 'max_price')
            ));
        }
            
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