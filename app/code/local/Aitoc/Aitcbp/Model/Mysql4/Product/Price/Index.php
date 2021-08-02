<?php
class Aitoc_Aitcbp_Model_Mysql4_Product_Price_Index extends Aitoc_Aitcbp_Model_Mysql4_Product_Price_Compatible
{
   
    public function reindexAll()
    {
        if (version_compare(Mage::getVersion(), '1.4.1.0') >= 0)
        {
            $this->useIdxTable(true);      
        }
        $this->_prepareIdxData();
        $this->_applyRules();
        $this->_copyIdxDataToMainTable();
        return $this;
    }

    public function reindexEntity($entityIds)
    {
        if (version_compare(Mage::getVersion(), '1.4.1.0') >= 0)
        {
            $this->useIdxTable(true);      
        }
        $this->_prepareIdxData($entityIds);
        $this->_applyRules();
        $this->_copyIdxDataToMainTable($entityIds);
        return $this;
    }

    protected function _prepareIdxTable()
    {
        $write = $this->_getWriteAdapter();
        $table = $this->getIdxTable();

		$write->commit();//because DDL statements are not allowed in transactions in Magento >= 1.7
		
        $query = sprintf('DROP TABLE IF EXISTS %s', $write->quoteIdentifier($table));
        $write->query($query);

        $query = sprintf('CREATE TABLE %s ('
            . ' `entity_id` INT(10) UNSIGNED NOT NULL,'
            . ' `website_id` SMALLINT(5) UNSIGNED NOT NULL,'
            . ' `group_id` SMALLINT(5) UNSIGNED NOT NULL,'  
            . ' `price` DECIMAL(12,4) DEFAULT NULL,'
            . ' PRIMARY KEY (`entity_id`,`website_id`)'
            . ') ENGINE=MYISAM DEFAULT CHARSET=utf8',
            $write->quoteIdentifier($table));
        $write->query($query);

        return $this;
    }


    protected function _prepareIdxData($entityIds = null)
    {
        $table = $this->getIdxTable();
        $this->_prepareIdxTable();

        $write  = $this->_getWriteAdapter();
        $select = $write->select()
            ->from(
                array('e' => $this->getTable('catalog/product')), 
                array('entity_id'))
            ->join(
                array('cw' => $this->getTable('core/website')),
                '',
                array('website_id'))
            ->join(
                array('csg' => $this->getTable('core/store_group')),
                'csg.website_id = cw.website_id AND cw.default_group_id = csg.group_id',
                array())
            ->join(
                array('cs' => $this->getTable('core/store')),
                'csg.default_store_id = cs.store_id',
                array());

        $statusCond = $write->quoteInto('=?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->_addAttributeToSelect($select, 'status', 'e.entity_id', 'cs.store_id', $statusCond, true);
        
        $group   = $this->_addAttributeToSelect($select, 'cbp_group', 'e.entity_id', 'cs.store_id', '<>0');
        $cost    = $this->_addAttributeToSelect($select, 'cost', 'e.entity_id', 'cs.store_id', '<>0');
                
        $select->columns(array(
            'group_id'    => $group,
            'price'       => $cost,
        ));

        if (!is_null($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        $query = $select->insertFromSelect($table);
        $write->query($query);
 
        return $this;
    }

    protected function _getRulesTable()
    {
        return $this->getIdxTable() . '_rules';
    }

    protected function _getGroupMarkupTable()
    {
        return $this->getIdxTable() . '_group_markup';
    }

    protected function _prepareRulesTable()
    {
        $write = $this->_getWriteAdapter();
        $table = $this->_getRulesTable();
		
		$write->commit();//because DDL statements are not allowed in transactions in Magento >= 1.7

        $query = sprintf('DROP TABLE IF EXISTS %s', $write->quoteIdentifier($table));
        $write->query($query);

        $query = sprintf('CREATE TABLE %s ('
            . ' `rule_id` SMALLINT(5) UNSIGNED NOT NULL,'
            . ' `group_id` SMALLINT(5) UNSIGNED NOT NULL,'  
            . ' `action_value` SMALLINT(5) UNSIGNED NOT NULL,'
            . ' `increase_decrease` DECIMAL(12,4) DEFAULT NULL,'
            . ' PRIMARY KEY (`rule_id`,`group_id`)'
            . ') ENGINE=MYISAM DEFAULT CHARSET=utf8',
            $write->quoteIdentifier($table));
        $write->query($query);

        return $this;
    }

    protected function _prepareGroupMarkupTable()
    {
        $write = $this->_getWriteAdapter();
        $table = $this->_getGroupMarkupTable();
		
		$write->commit();//because DDL statements are not allowed in transactions in Magento >= 1.7

        $query = sprintf('DROP TABLE IF EXISTS %s', $write->quoteIdentifier($table));
        $write->query($query);

        $query = sprintf('CREATE TABLE %s ('
            . ' `entity_id` INT(10) UNSIGNED NOT NULL,'
            . ' `website_id` SMALLINT(5) UNSIGNED NOT NULL,'
            . ' `group_id` SMALLINT(5) UNSIGNED NOT NULL,'  
            . ' `price` DECIMAL(12,4) DEFAULT NULL,'
            . ' PRIMARY KEY (`entity_id`,`website_id`)'
            . ') ENGINE=MYISAM DEFAULT CHARSET=utf8',
            $write->quoteIdentifier($table));
        $write->query($query);

        return $this;
    }

    protected function _applyRules()
    {
        $write      = $this->_getWriteAdapter();
        $rTable     = $this->_getRulesTable();
        $gmTable   = $this->_getGroupMarkupTable();

        $this->_prepareRulesTable();
        $this->_prepareGroupMarkupTable();

        $rules = Mage::getModel('aitcbp/rule')->getCollection();
        /* @var $rules Aitoc_Aitcbp_Model_Mysql4_Rule_Collection */
        $rules->addFilter('is_active', 1)->addOrder('priority');
        
        $rows = array();
            
        foreach ($rules as $rule) 
        {
            /* @var $rule Aitoc_Aitcbp_Model_Rule */
            $isWorking = false;
            $rule->load($rule->getId());
            $avgSale = Mage::helper('aitcbp')->getAvgSale($rule);

            if ( (false !== $avgSale) and (false !== $rule->getRevenueSum()) )
            {
                $evalConditions = '$isWorking = doubleval(' . $avgSale . ') ' . $rule->getRevenueClause() . ' doubleval(' . $rule->getRevenueSum() . ');';
                eval($evalConditions);
            }
            if ($isWorking) {
                $action = unserialize($rule->getActions());
                foreach (explode(',', $rule->getInGroups()) as $groupId)
                {
                   $rows[] = array(
                        'rule_id'           => $rule->getId(),
                        'group_id'          => $groupId,
                        'action_value'      => $action['action_value'],
                        'increase_decrease' => $action['increase_decrease'],
                    );
                }
            }
            
            if ($rule->getFutherRules()) break;
        }
        
        if (count($rows))
        {
            $write->insertArray($rTable, array('rule_id', 'group_id', 'action_value', 'increase_decrease'), $rows);
        }

        $select = $write->select()
            ->from(
                array('i' => $this->getIdxTable()),
                array('entity_id', 'website_id', 'group_id')
            )
            ->joinLeft(
                array('ra' => $this->_getRulesTable()),
                'ra.group_id = i.group_id',
                array()
            )
            ->join(
                array('g' => $this->getTable('aitcbp/group')),
                'g.entity_id = i.group_id',
                array());
        $select->group(array('entity_id', 'website_id'));    
    
        $rulesAmount = new Zend_Db_Expr("SUM(IF(ra.action_value = ".Aitoc_Aitcbp_Helper_Data::RULE_ACTION_INCREASE.", ra.increase_decrease, "
            . "IF(ra.action_value = ".Aitoc_Aitcbp_Helper_Data::RULE_ACTION_DECREASE.", 0 - ra.increase_decrease, 0)))");
        $markup = new Zend_Db_Expr("IF(g.cbp_type = ".Aitoc_Aitcbp_Helper_Data::MARGIN_TYPE_FIXED.", g.amount + {$rulesAmount}, "
            . "IF(g.cbp_type = ".Aitoc_Aitcbp_Helper_Data::MARGIN_TYPE_PERCENT.", i.price * (g.amount + {$rulesAmount}) / 100, 0))");
        
        $select->columns(array(
            'price'  => $markup,
        ));

        $query = $select->insertFromSelect($gmTable);   
        $write->query($query);
        
        $table  = array('i' => $this->getIdxTable());
        $select = $write->select()
            ->join(
                array('io' => $gmTable),
                'i.entity_id = io.entity_id AND i.website_id = io.website_id',
                array());
        $select->columns(array(
            'price' => new Zend_Db_Expr('IF(i.price IS NOT NULL, i.price + io.price, 0)'),
        ));
        $query = $select->crossUpdateFromSelect($table);
        $write->query($query);
        
		$write->commit();//because DDL statements are not allowed in transactions in Magento >= 1.7
		
        $write->truncate($rTable);
        $write->truncate($gmTable);   

        return $this;
    }
   
}