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



class Mirasvit_Advr_Model_Report_Abstract extends Mirasvit_Advr_Model_Report_Db_Collection
{
    protected $filterData = null;
    /**
     * @var Mirasvit_Advr_Model_Report_Select_Column[]
     */
    protected $columns = array();
    protected $expressions = array();
    protected $relations = array();

    protected $joinedTables = array();
    protected $selectedColumns = array();

    private $rangeFilterTable = 'sales_order_table';

    public function getFilterData()
    {
        return $this->filterData;
    }

    public function changeRelationCondition($table1, $table2, $newCondition)
    {
        foreach ($this->relations as $idx => $relation) {
            if (($relation[0] === $table1 && $relation[1] === $table2)
                || ($relation[0] === $table2 && $relation[1] === $table1)) {
                $this->relations[$idx][2] = $newCondition;
                return $this;
            }
        }
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getColumn($columnId)
    {
        if (isset($this->columns[$columnId])) {
            return $this->columns[$columnId];
        }

        return false;
    }

    public function getOrigColumnExpr($columnId)
    {
        if (isset($this->expressions[$columnId])) {
            return $this->expressions[$columnId];
        }

        return false;
    }

    public function addColumn($key, $data)
    {
        $column = Mage::getModel('advr/report_select_column')
            ->addData($data)
            ->setId($key);

        $this->columns[$key] = $column;
        $this->expressions[$key] = $column->getExpression();

        return $this;
    }

    /**
     * Set table used for the date range filter.
     *
     * @param string $table - table alias
     *
     * @return $this
     */
    public function setRangeFilterTable($table)
    {
        $this->rangeFilterTable = $table;

        return $this;
    }

    /**
     * Get table used for the date range filter.
     *
     *
     * @return string $table - table alias
     */
    public function getRangeFilterTable()
    {
        return $this->rangeFilterTable;
    }

    public function setBaseTable($table, $isSelectTimeTable = false)
    {
        if ($isSelectTimeTable && $table === 'sales/order') {
            $table = $this->selectTimeColumnTable('table');
        }

        $this->joinedTables[$table] = true;

        $tableAlias = str_replace('/', '_', $table).'_table';

        $this->mainTableAlias = $tableAlias;
        $this->getSelect()->from(
            array(str_replace('/', '_', $table).'_table' => $this->getTable($table)),
            array()
        );

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function joinRelatedDependencies($table, $alreadyUsed = '', $conditions = array())
    {
        if (isset($this->joinedTables[$table])) {
            return true;
        }

        foreach ($this->relations as $relation) {
            $leftTable = $relation[0];
            $rightTable = $relation[1];

            if (!is_array($relation[2])) {
                $condition = $conditions;
                $condition[] = $relation[2];
            } else {
                $condition = array_merge($relation[2], $conditions);
            }

            if (isset($relation[3])) {
                $conditionProvider = $relation[3];
            } else {
                $conditionProvider = false;
            }

            if ($table == $leftTable && $alreadyUsed != $rightTable) {
                if ($this->joinRelatedDependencies($rightTable, $leftTable, array())) {
                    $this->joinTable($leftTable, $condition, $conditionProvider);

                    return true;
                }
            } elseif ($table == $rightTable && $alreadyUsed != $leftTable) {
                if ($this->joinRelatedDependencies($leftTable, $rightTable, array())) {
                    $this->joinTable($rightTable, $condition, $conditionProvider);

                    return true;
                }
            }
        }

        return false;
    }

    protected function joinTable($table, $condition, $conditionProvider = false)
    {
        if (!isset($this->joinedTables[$table]) || $this->joinedTables[$table] == 2) {
            $tableName = str_replace('/', '_', $table).'_table';

            if ($conditionProvider) {
                $condition = Mage::getModel($conditionProvider)->getConditions($this, $condition);
            }

            if (is_array($condition)) {
                $condition = implode(' AND ', $condition);
            }

            $this->getSelect()
                ->joinLeft(
                    array($tableName => $this->getTable($table)),
                    $condition,
                    array()
                );

            $this->joinedTables[$table] = true;
        }

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function selectColumns($columns)
    {
        if (!is_array($columns)) {
            $columns = array($columns);
        }

        foreach ($columns as $column) {
            if (isset($this->columns[$column])) {
                $definition = $this->columns[$column];
                if (isset($definition['expression'])) {

                    // replace column expression definition from column configuration
                    $columnConfig = Mage::helper('advr/column')->getGridColumn($column);
                    if ($columnConfig && $columnConfig->getExpression() && $columnConfig->getExpression() !== $definition['expression']) {
                        $definition['expression'] = $columnConfig->getExpression();
                    }

                    $expr = $this->getExpression($definition);
                } elseif (isset($definition['expression_method'])) {
                    $expr = call_user_func(array($this, $definition['expression_method']));
                }

                if (isset($definition['table_method'])) {
                    if (isset($definition['table_args'])) {
                        $args = $definition['table_args'];
                    } else {
                        $args = array();
                    }
                    Mage::getModel($definition['table_method'])->join($this, $args);
                } elseif (isset($definition['table'])) {
                    $this->joinRelatedDependencies($definition['table']);
                }

                if (isset($expr) && !isset($this->selectedColumns[$column])) {
                    $this->getSelect()->columns(array($column => new Zend_Db_Expr($expr)));
                    $this->selectedColumns[$column] = true;
                }
            } elseif (strpos($column, 'percent') === false && $column != 'actions') {
                // Mage::throwException("Undefined column '$column'");
            }
        }

        return $this;
    }

    protected function getCurrencyScope()
    {
        $storeIds = ($this->getFilterData()) ? $this->getFilterData()->getStoreIds() : array();
        $currency = 'global_currency_code';

        if (is_array($storeIds) && count($storeIds) === 1) {
            $currency = 'order_currency_code';
        }

        return $currency;
    }

    protected function getExpression($data)
    {
        // in case of the catalog reports with child for bundle products
        if ($this->getFilterData()->getIncludeChild() && isset($data['expression_child'])) {
            $data['expression'] = $data['expression_child'];
        }

        $expression = $data['expression'];
        if (isset($data['type']) &&
            $data['type'] == 'currency' &&
            strpos($expression, 'base') !== false &&
            $this->getCurrencyScope() == 'global_currency_code'
        ) {
            $tableAlias = in_array($this->mainTableAlias, array('sales_order_table', 'sales_invoice_table', 'sales_creditmemo_table'))
                ? $this->mainTableAlias
                : 'sales_order_table';

            $expression = $this->strReplaceBrackets('(','((',$expression);
            $expression = $this->strReplaceBrackets(')','))',$expression);
            $expression = $this->strReplaceBrackets(
                ')',
                ' * IF('.$tableAlias.'.base_to_global_rate != 0, '.$tableAlias.'.base_to_global_rate, 1))',
                $expression
            );
        }

        return $expression;
    }

    protected function strReplaceBrackets($search, $replace, $text)
    {
        $pos = ($search === '(') ? strpos($text, $search): strrpos($text, $search);

        return $pos!==false ? substr_replace($text, $replace, $pos, 1) : $text;
    }

    protected function _initSelect()
    {
        return $this;
    }

    public function groupByColumn($column)
    {
        if (isset($this->columns[$column])) {
            $definition = $this->columns[$column];
            if (isset($definition['expression'])) {
                $expr = $definition['expression'];
            } elseif (isset($definition['expression_method'])) {
                $expr = call_user_func(array($this, $definition['expression_method']));
            }

            $tableName = $definition['table'];
            $methodName = 'join'.uc_words($tableName, '');

            if (method_exists($this, $methodName)) {
                call_user_func(array($this, $methodName));
            }

            $this->getSelect()->group(new Zend_Db_Expr($expr));
        } elseif (strpos($column, 'percent') === false) {
            Mage::throwException("Undefined column '$column'");
        }

        return $this;
    }

    public function getTotals()
    {
        $totals = array();

        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::GROUP);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        try {
            $rows = $this->getConnection()->fetchAll($select);
        } catch (Exception $e) {
            Mage::helper('advr/column')->handleCollectionFetchError($e);
        }

        foreach ($rows as $row) {
            foreach ($row as $k => $v) {
                if (!isset($totals[$k])) {
                    $totals[$k] = null;
                }

                if (!is_numeric($v)) {
                    continue;
                }

                $totals[$k] += $v;
                $totals[$k] = round($totals[$k], 2);
            }
        }

        return new Varien_Object($totals);
    }

    public function addFieldToFilter($field, $condition = null)
    {
        $this->selectColumns($field);
        $columnExpression = $this->_columnExpression($field);

        if (strpos($columnExpression, 'COUNT(') !== false
            || strpos($columnExpression, 'AVG(') !== false
            || strpos($columnExpression, 'SUM(') !== false
            || strpos($columnExpression, 'CONCAT(') !== false
            || strpos($columnExpression, 'MIN(') !== false
            || strpos($columnExpression, 'MAX(') !== false
        ) {
            $this->getSelect()->having($this->_translateCondition($columnExpression, $condition));
        } elseif ($condition) {
            parent::addFieldToFilter($columnExpression, $condition);
        }

        // echo Mirasvit_SqlFormatter::format($this->getSelect());

        return $this;
    }

    public function setOrder($field, $direction = 'DESC')
    {
        $this->selectColumns($field);

        $columnExpression = $this->_columnExpression($field);

        $this->getSelect()->order("$columnExpression $direction");

        return $this;
    }

    protected function _columnExpression($field)
    {
        $columns = $this->getSelect()->getPart(Zend_Db_Select::COLUMNS);
        foreach ($columns as $column) {
            if ($column[2] == $field) {
                if (is_object($column[1])) {
                    $expr = $column[1]->__toString();
                } else {
                    $expr = $column[1];
                }

                return $expr;
            }
        }

        return $field;
    }

    protected function _getRangeExpression($range)
    {
        switch ($range) {
            case '1h':
                $expr = $this->getConnection()->getConcatSql(array(
                    $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-%d %H:00:00'),
                    $this->getConnection()->quote('00'),
                ));
                break;

            case '1d':
                $expr = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-%d 00:00:00');
                break;

            case '1w':
                $attr = new Zend_Db_Expr('IF(MONTH({{attribute}}) = 1 AND WEEKOFYEAR({{attribute}}) > 50, DATE_FORMAT( DATE_SUB({{attribute}}, INTERVAL 1 YEAR), "%Y"), DATE_FORMAT({{attribute}}, "%Y"))');
                $year = new Zend_Db_Expr('WEEKOFYEAR({{attribute}})');

                $monday = new Zend_Db_Expr("'Monday'");
                $contact = $this->getConnection()->getConcatSql(array($attr, $year, $monday), ' ');

                $expr = $this->getConnection()->getConcatSql(
                    array("STR_TO_DATE($contact, '%x %v %W')", "'00:00:00'"),
                    ' '
                );


                break;

            default:
            case '1m':
                $expr = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-01 00:00:00');
                break;

            case '1q':
                $year = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y');
                $quarter = new Zend_Db_Expr('QUARTER({{attribute}})');
                $expr = $this->getConnection()->getConcatSql(array($year, $quarter, "'01 00:00:00'"), '-');

                break;

            case '1y':
                $expr = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-01-01 00:00:00');
                break;
        }

        return $expr;
    }

    protected function _getRangeExpressionForAttribute($range, $attribute)
    {
        $expression = $this->_getRangeExpression($range);

        return str_replace('{{attribute}}', $this->getConnection()->quoteIdentifier($attribute), $expression);
    }

    /**
     * The function makes it possible to build sales dependencies on the time (of an order creation or last order update)
     *
     * @param bool $ct - column or table
     *
     * @return string
     */
    public function selectTimeColumnTable($ct = false)
    {
        $table = 'sales/order';
        $column = $this->getRangeFilterTable().'.created_at';
        $type = Mage::getSingleton('advr/config')->getTimeOfCreatingBy();

        if ($type == 'last_update') {
            $column = $this->getRangeFilterTable().'.updated_at';
            $table = 'sales/order';
        }

        return ($ct === 'column') ? $column : $table;
    }

    public function checkSameTZ($column, $from, $to)
    {
        $yearEnd = new Zend_Date();
        $yearEnd
            ->setMonth(12)
            ->setDay(31)
            ->setTime('23:59:59');

        $periods = $this->_getTZOffsetTransitions(
            Mage::app()->getLocale()->storeDate(null)->toString(Zend_Date::TIMEZONE_NAME),
            time() - 3 * 365 * 24 * 60 * 60,
            $yearEnd
        );

        $i = 0;
        foreach ($periods as $offset => $timestamps) {
            foreach ($timestamps as $ts) {
                $periodFrom = trim($ts['from']->__toString(),"'");
                $periodTo = trim($ts['to']->__toString(),"'");

                if ($periodFrom <= $from && $from <= $periodTo
                    && $periodFrom <= $to && $to <= $periodTo) {
                    $then = $this->getConnection()->getDateAddSql(
                        $column,
                        $offset,
                        Varien_Db_Adapter_Interface::INTERVAL_SECOND
                    );

                    return new Zend_Db_Expr($then);
                }
            }
        }

        return false;
    }

    public function getTZDate($column, $typeDataCreated = false, $from = false, $to = false)
    {
        if ($typeDataCreated) {
            $column = $this->selectTimeColumnTable('column');
        }

        if ($from && $to) {
            $theSameTZ = $this->checkSameTZ($column, $from, $to);
            if ($theSameTZ) {
                return $theSameTZ;
            }
        }

        if (Mage::registry('ignore_tz')) {
            return $column;
        }
        $offset = Mage::getSingleton('core/date')->getGmtOffset();

        $yearEnd = new Zend_Date();
        $yearEnd
            ->setMonth(12)
            ->setDay(31)
            ->setTime('23:59:59');

        $periods = $this->_getTZOffsetTransitions(
            Mage::app()->getLocale()->storeDate(null)->toString(Zend_Date::TIMEZONE_NAME),
            time() - 3 * 365 * 24 * 60 * 60,
            //null
            $yearEnd
        );

        if (!count($periods)) {
            return $column;
        }

        $query = '';
        $periodsCount = count($periods);

        $i = 0;
        foreach ($periods as $offset => $timestamps) {
            $subParts = array();
            foreach ($timestamps as $ts) {
                $subParts[] = "($column between {$ts['from']} and {$ts['to']})";
            }

            $then = $this->getConnection()->getDateAddSql(
                $column,
                $offset,
                Varien_Db_Adapter_Interface::INTERVAL_SECOND
            );

            $query .= (++$i == $periodsCount) ? $then : 'CASE WHEN '.implode(' OR ', $subParts)." THEN $then ELSE ";
        }

        return new Zend_Db_Expr($query.str_repeat('END ', count($periods) - 1));
    }

    protected function _getTZOffsetTransitions($timezone, $from = null, $to = null)
    {
        $tzTransitions = array();

        try {
            if ($from == null) {
                $from = new Zend_Date(
                    $from,
                    Varien_Date::DATETIME_INTERNAL_FORMAT,
                    Mage::app()->getLocale()->getLocaleCode()
                );
                $from = $from->getTimestamp();
            }

            $to = new Zend_Date($to, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
            $nextPeriod = $this->getConnection()->formatDate($to->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
            $to = $to->getTimestamp();

            $dtz = new DateTimeZone($timezone);
            $transitions = $dtz->getTransitions();
            for ($i = count($transitions) - 1; $i >= 0; --$i) {
                $tr = $transitions[$i];
                if (!$this->_isValidTransition($tr, $to)) {
                    continue;
                }

                $tr['time'] = $this->getConnection()
                    ->formatDate($tr['time']);
                $tzTransitions[$tr['offset']][] = array('from' => $tr['time'], 'to' => $nextPeriod);

                if (!empty($from) && $tr['ts'] < $from) {
                    break;
                }
                $nextPeriod = $tr['time'];
            }
        } catch (Exception $e) {
            $this->_logException($e);
        }

        return $tzTransitions;
    }

    protected function _isValidTransition($transition, $to)
    {
        $result = true;
        $timeStamp = $transition['ts'];
        $transitionYear = date('Y', $timeStamp);

        if ($transitionYear > 10000 || $transitionYear < -10000) {
            $result = false;
        } elseif ($timeStamp > $to) {
            $result = false;
        }

        return $result;
    }

    protected function _translateCondition($field, $condition)
    {
        $field = $this->_getMappedField($field);

        return $this->_getConditionSql($field, $condition);
    }

    public function getSelectCountSql()
    {
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::HAVING);
        $countSelect->columns();

        $select = 'SELECT COUNT(*) FROM ('.$countSelect->__toString().') as cnt';

        return $select;
    }

    public function setFilterData($data)
    {
        foreach ($data->getData() as $column => $value) {
            if (isset($this->columns[$column])) {
                $this->columns[$column]->setValue($value);
            }
        }

        return $this;
    }

    /**
     * Increases session MySQL variable "group_concat_max_len"
     *
     * the default value of the "group_concat_max_len" (1024) limits the length of GROUP_CONCAT(),
     * and in the case of a large number of orders does not allow to take into account all the orders
     *
     * @return $this
     */
    public function increaseGroupConcatMaxLen()
    {
        $maxLength = 15000;

        $adapter = Mage::getSingleton('core/resource');
        $read = $adapter->getConnection('core_read');

        $select = 'SHOW variables LIKE "group_concat_max_len";';
        $result = $read->fetchAll($select);
        foreach ($result as $k => $v) {
            if ('group_concat_max_len' === $v['Variable_name']) {
                $r = (int) $v['Value'];
            }
        }

        if ($r < $maxLength) {
            $write = $adapter->getConnection('core_write');
            $query = 'SET session group_concat_max_len = '. $maxLength;
            $write->query($query);
        }

        return $this;
    }

    /**
     * Temporary disable ONLI_FULL_GROUP_BY MySQL mode
     */
    public function disableOnlyFullGroupByMode()
    {
        $adapter = Mage::getSingleton('core/resource');
        $write = $adapter->getConnection('core_write');
        $query = "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        $write->query($query);

        return $this;
    }

    /**
     * Make SQL $expression ready for use.
     *
     * @param string $expression
     *
     * @return string
     */
    public function prepareExpression($expression)
    {
        return str_replace('-', '_', $expression);
    }

    /**
     * Check whether the table has already been joined.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function isJoined($tableName)
    {
        return isset($this->joinedTables[$tableName]);
    }

    /**
     * Mark the table as joined.
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function setIsJoined($tableName)
    {
        $this->joinedTables[$tableName] = true;

        return $this;
    }
}
