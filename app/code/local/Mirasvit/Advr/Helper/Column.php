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



class Mirasvit_Advr_Helper_Column extends Mage_Core_Helper_Abstract
{
    public function getCurrentGrid()
    {
        return Mage::app()->getLayout()->getBlock('grid');
    }

    public function getGridColumn($columnId)
    {
        $grid = $this->getCurrentGrid();
        if ($grid) {
            return $grid->getColumn($columnId);
        }

        return false;
    }

    public function getGridColumnByExpr($expr)
    {
        $grid = $this->getCurrentGrid();
        if ($grid) {
            foreach ($grid->getAllColumns() as $columnId => $column) {
                if ($column->getExpression() == $expr) {
                    return $column;
                }
            }
        }

        return false;
    }

    public function getColumnsConfiguration()
    {
        $grid = $this->getCurrentGrid();
        if ($grid) {
            return $grid->getColumnsConfiguration();
        }

        return array();
    }

    public function saveColumnsConfig($columns)
    {
        $grid = $this->getCurrentGrid();
        if ($grid) {
            $grid->saveConfiguration(new Varien_Object(array('columns' => $columns)));
        }
    }

    public function getCollectionColumn($columnId)
    {
        $grid = $this->getCurrentGrid();

        if ($grid) {
            $collection = $grid->getCollection();
            if ($collection) {
                return $grid->getColumn($columnId);
            }
        }

        return false;
    }

    public function getOrigColumnExpression($columnId)
    {
        $grid = $this->getCurrentGrid();

        if ($grid) {
            $collection = $grid->getCollection();
            if ($collection) {
                return $collection->getOrigColumnExpr($columnId);
            }
        }

        return false;
    }

    /**
     * Exception can be caused by custom column's expression.
     * If so restore column's original expression.
     *
     * @param Exception $e
     */
    public function handleCollectionFetchError(Exception $e)
    {
        $matches = array();
        $result = preg_match("/Unknown column '([\w_\.]+)'/mi", $e->getMessage(), $matches);
        if ($result && isset($matches[1])) {
            $isReplaced = false;
            $expression = $matches[1];

            // fix exact matches only
            if ($gridColumn = $this->getGridColumnByExpr($expression)) {
                $columns = $this->getColumnsConfiguration();
                foreach ($columns as $id => $column) {
                    $origExpr = $this->getOrigColumnExpression($id);
                    if ($column['expression'] === $expression && $column['expression'] !== $origExpr) {
                        $columns[$id]['expression'] = $origExpr;
                    }
                }

                $this->saveColumnsConfig($columns);
                $isReplaced = true;
            }

            if (!$isReplaced) {
                // fix all approximate matches
                $columns = $this->getColumnsConfiguration();
                foreach ($columns as $id => $column) {
                    $origExpr = $this->getOrigColumnExpression($id);
                    if ($column['expression'] !== $origExpr && strpos($column['expression'], $expression) !== false) {
                        $columns[$id]['expression'] = $origExpr;
                    }
                }

                $this->saveColumnsConfig($columns);
            }
        } elseif (strpos($e->getMessage(), 'You have an error in your SQL syntax') !== false
            || strpos($e->getMessage(), 'Invalid use of group function') !== false
            || strpos($e->getMessage(), 'Syntax error') !== false
        ) {
            // fix all custom columns
            $columns = $this->getColumnsConfiguration();
            foreach ($columns as $id => $column) {
                $origExpr = $this->getOrigColumnExpression($id);
                if ($column['expression'] !== $origExpr) {
                    $columns[$id]['expression'] = $origExpr;
                }

                $this->saveColumnsConfig($columns);
            }
        }

        throw $e;
    }
}
