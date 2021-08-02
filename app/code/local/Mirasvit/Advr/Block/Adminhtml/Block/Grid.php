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



class Mirasvit_Advr_Block_Adminhtml_Block_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $currentCurrencyCode;
    protected $currentCurrencyScope;

    protected $afterCollectionLoadCallback;

    public function _prepareLayout()
    {
        $this->setTemplate('mst_advr/block/grid.phtml');
        $this->setId($this->getNameInLayout());

        $this->setChild(
            'save_config_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'label' => Mage::helper('adminhtml')->__('Save configuration'),
                        'onclick' => 'grid_configuration.submit();',
                        'class' => 'save',
                    )
                )
        );

        if ($this->getId() == 'Mirasvit_Advr_Block_Adminhtml_Order_Plain') {
            $this->currentCurrencyScope = 'order_currency_code';
        }

        return parent::_prepareLayout();
    }

    public function afterCollectionLoad($callback)
    {
        $this->afterCollectionLoadCallback = $callback;

        return $this;
    }

    public function setPagerVisibility($visible = true)
    {
        parent::setPagerVisibility($visible);

        return $this;
    }

    protected function _prepareCollection()
    {
        parent::_prepareCollection();

        if ($this->afterCollectionLoadCallback) {
            call_user_func($this->afterCollectionLoadCallback);
        }

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addColumn($columnId, $column)
    {
        $column['header'] = Mage::helper('advr')->__($column['header']);

        if (!isset($column['type'])) {
            $column['type'] = 'text';
        }

        if ($column['type'] == 'currency') {
            $column['currency_code'] = $this->getCurrentCurrencyCode();
            $column['currency'] = $this->getCurrencyScope();
        } elseif ($column['type'] == 'percent') {
            $column['column_css_class'] = 'nobr percent';
            $column['filter'] = false;
        } elseif ($column['type'] == 'text' || $column['type'] == 'options') {
            if (!isset($column['totals_label'])) {
                $column['totals_label'] = '';
                $column['filter_totals_label'] = '';
            }
        }

        if (!isset($column['index'])) {
            $column['index'] = $columnId;
        }

        if (!isset($column['chart'])) {
            $column['chart'] = false;
        }

        if ($columnId == 'actions') {
            $column['totals_label'] = '';
            $column['width'] = '50px';
            $column['filter'] = false;
            $column['sortable'] = false;
            $column['is_system'] = true;
            $column['renderer'] = isset($column['renderer']) ? $column['renderer'] : 'Mirasvit_Advr_Block_Adminhtml_Block_Grid_Renderer_Action';
        }

        $configuration = $this->getColumnsConfiguration();

        if (isset($configuration[$columnId])) {
            $column = array_merge($column, $configuration[$columnId]);
            if (isset($column['hidden']) && $column['hidden']) {
                $column['is_system'] = 1;
            }
        }

        if (is_array($column)) {
            $this->_columns[$columnId] = $this->getLayout()->createBlock('advr/adminhtml_block_grid_column')
                ->setData($column)
                ->setGrid($this);
        }

        $this->_columns[$columnId]->setId($columnId);
        $this->_lastColumnId = $columnId;

        return $this;
    }

    public function getAllColumns()
    {
        $columns = parent::getColumns();

        $position = 10;
        $positions = array();
        foreach ($columns as $index => $column) {

            if ($this->getCollection() && !$column->getExpression()) {
                $columnModel = $this->getCollection()->getColumn($index);
                if ($columnModel && is_object($columnModel) && $columnModel->getExpression()) {
                    $column->setExpression($columnModel->getExpression());
                }
            }

            if (!$columns[$index]->getPosition()) {
                $columns[$index]->setPosition($position);
            }

            $positions[$index] = $column->getPosition();
            $position += 10;
        }

        asort($positions);

        $sorted = array();
        foreach ($positions as $index => $position) {
            $sorted[$index] = $columns[$index];
        }

        return $sorted;
    }

    public function getColumns()
    {
        $columns = $this->getAllColumns();

        foreach ($columns as $index => $column) {
            if ($column->getHidden()) {
                unset($columns[$index]);
            }
        }

        return $columns;
    }

    public function getColumn($columnId)
    {
        $columns = $this->getColumns();
        if (isset($columns[$columnId])) {
            return $columns[$columnId];
        }

        return false;
    }

    public function getCurrentCurrencyCode()
    {
        if (is_null($this->currentCurrencyCode)) {
            if (count($this->getStoreIds()) > 0) {
                $storeIds = $this->getStoreIds();
                $this->currentCurrencyCode = Mage::app()->getStore(array_shift($storeIds))->getBaseCurrencyCode();
            } else {
                $this->currentCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
            }
        }

        return $this->currentCurrencyCode;
    }

    public function getCurrencyScope()
    {
        if (is_null($this->currentCurrencyScope)) {
            $this->currentCurrencyScope = (count($this->getStoreIds()) > 0)
                ? 'order_currency_code'
                : 'global_currency_code';
        }

        return $this->currentCurrencyScope;
    }

    public function getRowUrl($item)
    {
        if ($this->getRowUrlCallback()) {
            return call_user_func_array($this->getRowUrlCallback(), array($item));
        }

        return false;
    }

    public function getJsObjectName()
    {
        return 'advnGridJsObject';
    }

    public function addExportType($url, $label)
    {
        $this->_exportTypes[] = new Varien_Object(
            array(
                'url' => $this->getUrl(
                    '*/*/*',
                    array('_current' => true, '_query' => array('export' => true, 'type' => $url))
                ),
                'label' => $label,
            )
        );

        return $this;
    }

    public function saveConfiguration($configuration)
    {
        Mage::helper('advr')->setVariable($this->getId(), $configuration);

        return $this;
    }

    public function getColumnsConfiguration()
    {
        $configuration = Mage::helper('advr')->getVariable($this->getId());

        if ($configuration instanceof Varien_Object) {
            $columns = $configuration->getColumns();
            if (is_array($columns)) {
                return $columns;
            }
        }

        return array();
    }

    public function getColumnsOrder()
    {
        $columnsOrder = array();
        $columns = array_keys($this->getColumns());
        foreach ($columns as $index => $code) {
            $columnsOrder[$code] = ($index == 0) ? '' : $columns[$index - 1];
        }

        return $columnsOrder;
    }
}
