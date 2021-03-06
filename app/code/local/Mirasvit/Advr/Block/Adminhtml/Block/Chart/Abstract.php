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



class Mirasvit_Advr_Block_Adminhtml_Block_Chart_Abstract extends Mage_Adminhtml_Block_Template
{
    protected $collection;
    protected $options = array();
    protected $columns = array();
    protected $resetLimit = true;

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function addOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function getOptionsAsJson()
    {
        return Zend_Json::encode($this->options);
    }

    public function addColumn($label, $field, $type = 'string')
    {
        $this->columns[] = new Varien_Object(array(
            'label' => $label,
            'field' => $field,
            'type'  => $type,
        ));

        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function resetColumns()
    {
        $this->columns = array();

        return $this;
    }

    protected function _castValue($column, $value)
    {
        switch ($column->getType()) {
            case 'label':
            case 'string':
                $value = '' . $value . '';
                break;

            case 'number':
            case 'decimal':
            case 'float':
                $value = floatval($value);
                break;
        }

        return $value;
    }

    public function getColumnTypes()
    {
        $types = array();
        foreach ($this->columns as $column) {
            if ($this->_isColumnAllowed($column)) {
                $types[] = $column['type'];
            }
        }

        return $types;
    }

    public function getColumnColors()
    {
        $colors = array();
        foreach ($this->columns as $index => $column) {
            if ($this->_isColumnAllowed($column)) {
                $colors[] = Mage::getSingleton('advr/config')->getChartColumnColor($index);
            }
        }

        return $colors;
    }

    public function getCollection()
    {
        if ($this->collection == null) {
            $this->collection = $this->getData('collection');
            if ($this->resetLimit) {
                $this->collection->setPageSize(10000)
                    ->clear();
            }
        }

        return $this->collection;
    }

    /**
     * Whether the collection's limit should be reset or not (show chart only for page size)
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setResetLimit($flag)
    {
        $this->resetLimit = $flag;

        return $this;
    }

    protected function _isColumnAllowed($column)
    {
        if (!in_array($column->getType(), array('number', 'currency'))) {
            return false;
        }

        if ($column->getChart() === 'none') {
            return false;
        }

        return true;
    }

    /**
     * Get current report title.
     *
     * @return string
     */
    public function getTitle()
    {
        $title = 'Chart Report.png';
        $head  = $this->getLayout()->getBlock('head');

        if ($head) {
            $title = $head->getTitle() . ' ' . $title;
        }

        return $title;
    }
}
