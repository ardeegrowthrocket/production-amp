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



class Mirasvit_Advr_Block_Adminhtml_Block_Container extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Mirasvit_Advr_Block_Adminhtml_Block_Toolbar
     */
    protected $toolbar;

    /**
     * @var Mirasvit_Advr_Block_Adminhtml_Block_Grid
     */
    protected $grid;

    protected $chart;
    protected $storeSwitcher;

    public function _prepareLayout()
    {
        $this->prepareStoreSwitcher()
            ->prepareToolbar()
            ->prepareGrid()
            ->prepareChart();

        $this->setTemplate('mst_advr/block/container.phtml');

        return parent::_prepareLayout();
    }

    public function getGrid()
    {
        return $this->grid;
    }

    public function getToolbar()
    {
        return $this->toolbar;
    }

    public function getChart()
    {
        return $this->chart;
    }

    public function getStoreSwitcher()
    {
        return $this->storeSwitcher;
    }

    /**
     * @return $this
     */
    protected function prepareStoreSwitcher()
    {
        $this->initStoreSwitcher();

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareToolbar()
    {
        $this->initToolbar();

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareGrid()
    {
        $this->initGrid();

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareChart()
    {
        $this->initChart();

        return $this;
    }

    /**
     * @return $this
     */
    protected function initStoreSwitcher()
    {
        $this->storeSwitcher = Mage::app()->getLayout()->createBlock('adminhtml/store_switcher')
            ->setTemplate('mst_advr/block/store_switcher.phtml')
            ->setStoreVarName('store_ids');

        return $this;
    }

    /**
     * @return Mirasvit_Advr_Block_Adminhtml_Block_Toolbar
     */
    protected function initToolbar()
    {
        $this->toolbar = Mage::app()->getLayout()->createBlock('advr/adminhtml_block_toolbar');

        $this->toolbar
            ->setFilterData($this->getFilterData())
            ->setVisibility(true)
            ->setRangesVisibility(false)
            ->setCompareVisibility(false)
            ->setIntervalsVisibility(true)
            ->setSalesSourceVisibility(false)
            ->setContainer($this);

        return $this->toolbar;
    }

    /**
     * @return Mirasvit_Advr_Block_Adminhtml_Block_Grid
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function initGrid2()
    {
    }

    protected function initGrid()
    {
        $this->grid = Mage::app()->getLayout()->createBlock('advr/adminhtml_block_grid', get_class($this))
            ->setNameInLayout('grid')
            ->setStoreIds($this->getFilterData()->getStoreIds());

        foreach ($this->getColumns() as $columnId => $column) {
            $this->grid->addColumn($columnId, $column);

            if (isset($column['grouped'])) {
                $this->grid->isColumnGrouped($columnId, 1);
            }
        }

        $this->grid->setContainer($this)
            ->setFilterData($this->getFilterData())
            ->afterCollectionLoad(array($this, 'afterGridCollectionLoad'));

        $this->grid->setCollection($this->getCollection());

        $totals = $this->getTotals();

        if ($totals) {
            $this->grid->setTotals($totals);
            $this->grid->setCountTotals(1);
        }

        $this->grid->addExportType('csv', Mage::helper('advr')->__('CSV'));
        $this->grid->addExportType('xml', Mage::helper('advr')->__('Excel XML'));

        return $this->grid;
    }

    public function afterGridCollectionLoad()
    {
        #subtotal collection
        $totals = $this->getTotals();

        if ($totals && $totals != $this->grid->getTotals()) {
            $this->grid->setFilterTotals($totals);
            $this->grid->setFilterCountTotals(1);
        }

        return $this;
    }

    /**
     * @return Mirasvit_Advr_Block_Adminhtml_Block_Chart_Abstract
     */
    protected function initChart()
    {
        $blockType = 'advr/adminhtml_block_chart_'.$this->getChartType();

        $this->chart = Mage::app()->getLayout()->createBlock($blockType);

        $this->chart
            ->setCollection($this->getCollection($this->getFilterData()))
            ->setColumns($this->grid->getColumns());

        return $this->chart;
    }

    public function getGridHtml()
    {
        if ($this->grid) {
            try {
                return $this->grid->toHtml();
            } catch (Exception $e) {
                Mage::helper('advr/column')->handleCollectionFetchError($e);
            }
        }

        return;
    }

    public function getToolbarHtml()
    {
        if ($this->toolbar) {
            return $this->toolbar->toHtml();
        }

        return;
    }

    public function getStoreSwitcherHtml()
    {
        if ($this->storeSwitcher) {
            return $this->storeSwitcher->toHtml();
        }

        return;
    }

    public function getChartHtml()
    {
        if ($this->chart) {
            return $this->chart->toHtml();
        }

        return;
    }

    public function getCollection($filterData = null)
    {
        if (!$filterData) {
            $filterData = $this->getFilterData();
        }

        $hash = md5(serialize($filterData->getData()));

        if (!$this->hasData($hash)) {
            $collection = $this->_prepareCollection();
            $this->setData($hash, $collection);
        }

        return $this->getData($hash);
    }

    public function getVisibleColumns()
    {
        $columns = array_keys($this->grid->getColumns());

        foreach ($this->grid->getColumns() as $column) {
            $columns[] = $column->getIndex();
        }

        if ($orderColumn = $this->grid->getParam($this->grid->getVarNameSort())) {
            $columns[] = $orderColumn;
        }

        $columns = array_unique(array_filter($columns));

        return $columns;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return Varien_Object
     */
    public function getFilterData()
    {
        if (!$this->hasData('filter_data')) {
            $data = Mage::helper('adminhtml')->prepareFilterString($this->getRequest()->getParam('filter'));
            $data = array_filter($data, array($this, 'filterNull'));

            $data['locale'] = Mage::app()->getLocale()->getLocaleCode();
            $data['date_format'] = Mage::getSingleton('advr/config')->dateFormat();

            # restore filters from cookies
            $savedData = Mage::getModel('core/cookie')->get('advr_filter_data');
            if ($savedData) {
                $savedData = Mage::helper('core')->jsonDecode($savedData);
                if (is_array($savedData)) {
                    foreach ($savedData as $key => $value) {
                        // set filters stored in cookies
                        if (!isset($data[$key])
                            && in_array($key, array('interval', 'from', 'to', 'range', 'remote_ip', 'sales_source'))
                        ) {
                            $data[$key] = $value;
                        }
                    }

                    if (isset($savedData['locale']) &&
                        isset($data['interval']) &&
                        Zend_Locale_Format::checkDateFormat($data['from'], array('date_format' => $savedData['date_format'], 'locale' => $savedData['locale']))
                    ) {
                        $data['locale'] = $savedData['locale'];
                        $data['date_format'] = $savedData['date_format'];
                    }
                }
            }

            # save filters to cookies
            Mage::getModel('core/cookie')->set('advr_filter_data', Mage::helper('core')->jsonEncode($data));

            $data = $this->_filterDates($data, array('from', 'to', 'compare_from', 'compare_to'));

            $currentMonth = Mage::helper('advr/date')->getInterval(Mirasvit_Advr_Helper_Date::THIS_MONTH);

            if (!isset($data['from'])) {
                $data['from'] = $currentMonth->getFrom()->get(Varien_Date::DATETIME_INTERNAL_FORMAT);
            }

            if (!isset($data['to'])) {
                $data['to'] = $currentMonth->getTo()->get(Varien_Date::DATETIME_INTERNAL_FORMAT);
            }

            if (strpos($data['from'], ':') === false) {
                $data['from'] .= ' 00:00:00';
            }
            if (isset($data['compare_from']) && strpos($data['compare_from'], ':') === false) {
                $data['compare_from'] .= ' 00:00:00';
            }

            if (strpos($data['to'], ':') === false) {
                $data['to'] .= ' 23:59:59';
            }
            if (isset($data['compare_to']) && strpos($data['compare_to'], ':') === false) {
                $data['compare_to'] .= ' 23:59:59';
            }

            if (!isset($data['range'])) {
                $data['range'] = '1d';
            }

            if (!isset($data['group_by'])) {
                $data['group_by'] = 'status';
            }

            if (!isset($data['sales_source']) || is_numeric($data['sales_source'])) {
                $data['sales_source'] = Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_ORDER;
            }

            $offset = Mage::getModel('core/date')->timestamp() - Mage::getModel('core/date')->gmtTimestamp();

            $fromLocal = new Zend_Date(strtotime($data['from']) - $offset);
            $data['from_local'] = $fromLocal->get(Varien_Date::DATETIME_INTERNAL_FORMAT);

            $toLocal = new Zend_Date(strtotime($data['to']) - $offset);
            $data['to_local'] = $toLocal->get(Varien_Date::DATETIME_INTERNAL_FORMAT);

            $data = $this->arrayFilter($data);

            $data['store_ids'] = array_filter(explode(',', $this->getRequest()->getParam('store_ids')));

            $this->setData('filter_data', new Varien_Object($data));
        }

        return $this->getData('filter_data');
    }

    public function arrayFilter($data)
    {
        if (isset($data['customer_group_id']) && ('0' === $data['customer_group_id'])) {
            $customerFlag = true;
        }

        $data = array_filter($data);

        if (isset($customerFlag)) {
            $data['customer_group_id'] = '0';
        }

        return $data;
    }

    public function getCompareFilterData()
    {
        if (!$this->getFilterData()->getCompare()) {
            return false;
        }

        $params = $this->getFilterData();
        $params->setFrom($this->getFilterData()->getCompareFrom());
        $params->setTo($this->getFilterData()->getCompareTo());

        return $params;
    }

    protected function _filterDates($array, $dateFields)
    {
        if (empty($dateFields)) {
            return $array;
        }

        $filterInput = new Zend_Filter_LocalizedToNormalized(array(
            'locale' => $array['locale'],
            'date_format' => $array['date_format'],
        ));

        $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
            'locale' => Mage::app()->getLocale()->getLocaleCode(),
            'date_format' => Varien_Date::DATETIME_INTERNAL_FORMAT,
        ));

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $array) && !empty($dateField)) {
                $array[$dateField] = $filterInput->filter($array[$dateField]);
                $array[$dateField] = $filterInternal->filter($array[$dateField]);
            }
        }

        return $array;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getFilterDataAsString()
    {
        if ($this->getFilterData()->getFrom()) {
            $html[] = 'From: '.$this->getFilterData()->getFrom();
        }

        if ($this->getFilterData()->getTo()) {
            $html[] = 'To: '.$this->getFilterData()->getTo();
        }

        foreach ($this->getGrid()->getColumns() as $column) {
            if ($column->getFilter()) {
                $condition = $column->getFilter()->getCondition();
                if (isset($condition['from'])) {
                    $html[] = $column->getHeader().' from '.$condition['from'];
                }
                if (isset($condition['to'])) {
                    $html[] = $column->getHeader().' to '.$condition['to'];
                }
                if (isset($condition['like']) && $condition['like'] != "'%%'") {
                    $html[] = $column->getHeader().' like '.$condition['like'];
                }
                if (isset($condition['eq'])) {
                    $html[] = $column->getHeader().' equal '.$condition['eq'];
                }
            }
        }

        return implode('<br>', $html);
    }

    public function getSubHeaderText()
    {
        return $this->getFilterDataAsString();
    }

    public function filterNull($el)
    {
        if (is_array($el)) {
            if (count($el) == 1 && isset($el['locale'])) {
                return 0;
            }

            return array_filter($el, array($this, 'filterNull'));
        }

        return strlen($el);
    }

    /**
     * Get array of columns that can be used to filter report collection
     * method can be inherited in specific report in order to restrict columns available for filter.
     *
     * @return array
     */
    public function getFilterColumns()
    {
        $columnsForFilter = array();
        if (method_exists($this->getCollection(), 'getColumns')) {
            $columnsForFilter = $this->getCollection()->getColumns();
        }

        return $columnsForFilter;
    }

    /**
     * Logic for calculation totals only of unique items.
     *
     * @param Mirasvit_Advr_Model_Report_Sales $collection - collection to calculate totals over
     * @param string $property - unique property by which every item can be identified in collection
     *
     * @return Varien_Object
     */
    public function getUniqueTotals($collection, $property)
    {
        $fields = array();
        $totals = new Varien_Object();
        $columns = $this->getColumns();
        foreach ($columns as $code => $column) {
            if (isset($column['type']) && in_array($column['type'], array('currency', 'number'))) {
                $fields[$code] = '0';
            }
        }

        $countedIds = array();
        $collectionClone = clone $collection;
        try {
            foreach ($collectionClone as $item) {
                // do not count the same item if it has been already counted previously
                if (in_array($item->getData($property), $countedIds)) {
                    continue;
                }

                foreach($fields as $field => $value){
                    $fields[$field] += $item->getData($field);
                }

                $countedIds[] = $item->getData($property); // register item
            }
        } catch (Exception $e) {
            Mage::helper('advr/column')->handleCollectionFetchError($e);
        }

        $totals->setData($fields);
        $this->setTotals($totals);

        return $totals;
    }

    /**
     * Modify base table name in case a report is build based on invoices instead of orders.
     *
     * @param string $defaultTable - base table name used for report
     *
     * @return string
     */
    protected function getBaseTable($defaultTable)
    {
        $salesSource = $this->getFilterData()->getData('sales_source');
        if ($defaultTable === 'sales/order'
            && $salesSource !== Mirasvit_Advr_Model_System_Config_Source_SalesSource::SALES_SOURCE_ORDER
        ) {
            $defaultTable = 'sales/' . $salesSource;
        }

        return $defaultTable;
    }
}
