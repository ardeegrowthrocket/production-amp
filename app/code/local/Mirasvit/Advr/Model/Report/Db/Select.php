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



class Mirasvit_Advr_Model_Report_Db_Select extends Varien_Db_Select
{
    const SQL_USE_INDEX  = 'USE INDEX';
    const SQL_FORCE_INDEX  = 'FORCE INDEX';
    const SQL_IGNORE_INDEX  = 'IGNORE INDEX';

    static private $i = array();


    /**
     * Specify index to use
     *
     * @return Zend_Db_Select
     */
//    public function useIndex($index)
//    {
//        if(empty($this->_parts[self::SQL_FORCE_INDEX])) {
//            if(!is_array($index)) {
//                $index = array($index);
//            }
//            $this->_parts[self::SQL_USE_INDEX] = $index;
//            return $this;
//        } else {
//            throw new Zend_Db_Select_Exception("Cannot use 'USE INDEX' in the same query as 'FORCE INDEX'");
//        }
//    }

    /**
     * Force index to use
     *
     * @return Zend_Db_Select
     */
//    public function forceIndex($index)
//    {
//        if(empty($this->_parts[self::SQL_USE_INDEX])) {
//            if(!is_array($index)) {
//                $index = array($index);
//            }
//            $this->_parts[self::SQL_FORCE_INDEX] = $index;
//            return $this;
//        } else {
//            throw new Zend_Db_Select_Exception("Cannot use 'FORCE INDEX' in the same query as 'USE INDEX'");
//        }
//    }

    /**
     * Ignore index
     *
     * @return Zend_Db_Select
     */
    public function ignoreIndex($index)
    {
        if (!is_array($index)) {
            return $this;
        }
        $this->_parts[self::SQL_IGNORE_INDEX] = $index;
        return $this;
    }

    /**
     * @inheritdoc
     * Render FROM clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderFrom($sql)
    {
        /*
         * If no table specified, use RDBMS-dependent solution
         * for table-less query.  e.g. DUAL in Oracle.
         */
        if (empty($this->_parts[self::FROM])) {
            $this->_parts[self::FROM] = $this->_getDummyTable();
        }

        $from = array();

        foreach ($this->_parts[self::FROM] as $correlationName => $table) {
            $tmp = '';

            $joinType = ($table['joinType'] == self::FROM) ? self::INNER_JOIN : $table['joinType'];

            // Add join clause (if applicable)
            if (! empty($from)) {
                $tmp .= ' ' . strtoupper($joinType) . ' ';
            }

            $tmp .= $this->_getQuotedSchema($table['schema']);
            $tmp .= $this->_getQuotedTable($table['tableName'], $correlationName);

            if (!empty($from) && ! empty($table['joinCondition'])) {

                self::$i[$correlationName] = 0;

                if (isset($this->_parts[self::SQL_IGNORE_INDEX])) {
                    foreach ($this->_parts[self::SQL_IGNORE_INDEX] as $index => $joinTableAllias) {
                        if ($correlationName === $joinTableAllias && 0 === self::$i[$correlationName]) {
                            $tmp .= ' ' . self::SQL_IGNORE_INDEX . ' ' . '(' . $index . ')' . ' ' . self::SQL_ON . ' ' . $table['joinCondition'];
                            self::$i[$correlationName]++;
                            break;
                        }
                    }
                }

                if (0 === self::$i[$correlationName]) {
                    $tmp .= ' ' . self::SQL_ON . ' ' . $table['joinCondition'];
                }
            }

            // Add the table name and condition add to the list
            $from[] = $tmp;
        }

        // Add the list of all joins
        if (!empty($from)) {
            $sql .= ' ' . self::SQL_FROM . ' ' . implode("\n", $from);
        }

        return $sql;
    }
}