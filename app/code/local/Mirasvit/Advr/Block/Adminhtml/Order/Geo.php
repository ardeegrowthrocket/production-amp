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



class Mirasvit_Advr_Block_Adminhtml_Order_Geo extends Mirasvit_Advr_Block_Adminhtml_Order_Abstract
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setHeaderText(Mage::helper('advr')->__('Sales By Geo-data (based on Postal Code)'));

        return $this;
    }

    protected function prepareChart()
    {
        if ($this->getGeoDimension() == 'postcode') {
            $this->setChartType('map');
        } else {
            $this->setChartType('geo');
        }

        $this->initChart()
            ->resetColumns();

        if ($this->getFilterData()->getCountryId()) {
            $this->getChart()
                ->addOption('region', $this->getFilterData()->getCountryId())
                ->addOption('resolution', 'provinces')
                ->addOption('enableRegionInteractivity', true);
        }

        switch ($this->getGeoDimension()) {
            case 'state':
            case 'province':
                $this->getChart()
                    ->addOption('displayMode', 'regions')
                    ->addColumn('State', 'state')
                    ->addColumn('Grand Total', $this->getColumn('sum_grand_total'), 'number')
                    ->addColumn('Number Of Orders', $this->getColumn('quantity'), 'number');
                break;
            case 'place':
                $this->getChart()
                    ->addOption('displayMode', 'markers')
                    ->addColumn('Latitude', 'lat', 'number')
                    ->addColumn('Longitude', 'lng', 'number')
                    ->addColumn('Label', 'place', 'string')
                    ->addColumn('Grand Total', $this->getColumn('sum_grand_total'), 'number')
                    ->addColumn('Number Of Orders', $this->getColumn('quantity'), 'number');
                break;

            case 'postcode':
                $this->getChart()
                    ->addOption('mapTypeId', 'google.maps.MapTypeId.TERRAIN')
                    ->addOption('zoom', 3)
                    ->addOption('center', array('A' => 40.00, 'F' => 0.))
                    ->addColumn('Latitude', 'lat', 'number')
                    ->addColumn('Longitude', 'lng', 'number')
                    ->addColumn('Place', 'place', 'label')
                    ->addColumn('Postal Code', 'postcode', 'label')
                    ->addColumn('Grand Total', $this->getColumn('sum_grand_total'), 'label')
                    ->addColumn('Number Of Orders', $this->getColumn('quantity'), 'label');
                break;

            default:
                $this->getChart()
                    ->addColumn('Country', 'country_id')
                    ->addColumn('Grand Total', $this->getColumn('sum_grand_total'), 'number');
                break;

        }

        return $this;
    }

    protected function prepareGrid()
    {
        $this->initGrid();

        $this->getGrid()
            ->setDefaultSort($this->getColumn('sum_grand_total'))
            ->setDefaultDir('desc')
            ->setDefaultLimit(200)
            ->setPagerVisibility(true);

        return $this;
    }

    protected function prepareToolbar()
    {
        $this->initToolbar();

        $this->getToolbar()
            ->setRangesVisibility(false)
            ->setCompareVisibility(false)
            ->setSalesSourceVisibility(true);

        $form = $this->getToolbar()->getForm();

        $form->addField('geo_dimension', 'radios', array(
            'name'   => 'geo_dimension',
            'label'  => Mage::helper('advr')->__('Group By'),
            'values' => array(
                array(
                    'value' => 'country_id',
                    'label' => $this->__('Country')
                ),
                array(
                    'value' => 'state',
                    'label' => $this->__('State')
                ),
                array(
                    'value' => 'province',
                    'label' => $this->__('Province')
                ),
                array(
                    'value' => 'place',
                    'label' => $this->__('Place')
                ),
                array(
                    'value' => 'postcode',
                    'label' => $this->__('Postal Code')
                ),
            ),
            'value'  => $this->getGeoDimension(),
        ));

        $form->addField('shipping_address', 'checkbox', array(
            'name' => 'shipping_address',
            'label' => Mage::helper('advr')->__('By Shipping Address'),
            'value' => 1,
            'checked' => $this->getShippingAddress(),
        ));

        return $this;
    }

    protected function getGroupByColumn()
    {
        if ($this->getGeoDimension()) {
            return $this->getColumn($this->getGeoDimension());
        } else {
            return $this->getColumn('country_id');
        }
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advr/report_sales');

        if ($this->getShippingAddress()) {
            $collection->changeRelationCondition(
                'sales/order',
                'sales/order_address',
                'sales_order_table.shipping_address_id = sales_order_address_table.entity_id'
            );
        }

        $collection->setBaseTable($this->getBaseTable('sales/order'), true)
            ->setFilterData($this->getFilterData())
            ->selectColumns(array('lat', 'lng'))
            ->selectColumns($this->getVisibleColumns());

        if ($this->getGeoDimension()) {
            $collection->groupByColumn($this->getGroupByColumn());
        } else {
            $collection->groupByColumn($this->getGroupByColumn());
        }

        return $collection;
    }

    public function getColumns()
    {
        $columns = array();

        $columns['country_id'] = array(
            'header'         => 'Country',
            'type'           => 'options',
            'frame_callback' => array(Mage::helper('advr/callback'), 'country'),
            'totals_label'   => 'Total',
            'options'        => Mage::getSingleton('advr/system_config_source_country')->toOptionHash(),
            'position'       => 1,
            'link_callback'  => array($this, 'countryLinkCallBack'),
            self::KEEP       => true
        );

        if (in_array($this->getGeoDimension(), array('postcode', 'state', 'province', 'place'))) {
            $columns['state'] = array(
                'header'        => 'State',
                'totals_label'  => '',
                'hidden'        => false,
                'position'      => 2,
                'link_callback' => array($this, 'stateLinkCallBack'),
                self::KEEP      => true
            );
        }

        if (in_array($this->getGeoDimension(), array('province'))) {
            $columns['province'] = array(
                'header'       => 'Province (District)',
                'totals_label' => '',
                'hidden'       => false,
                'position'     => 3,
                self::KEEP     => true
            );
        }

        if (in_array($this->getGeoDimension(), array('postcode', 'place'))) {
            $columns['place'] = array(
                'header'        => 'Place (City)',
                'totals_label'  => '',
                'hidden'        => false,
                'position'      => 4,
                'link_callback' => array($this, 'placeLinkCallBack'),
                self::KEEP      => true
            );
        }

        if (in_array($this->getGeoDimension(), array('postcode'))) {
            $columns['postcode'] = array(
                'header'       => 'Postal Code',
                'totals_label' => '',
                'hidden'       => false,
                'position'     => 5,
                self::KEEP     => true
            );
        }

        $columns['percent'] = array(
            'header'          => 'Number Of Orders, %',
            'type'            => 'percent',
            'index'           => 'quantity',
            'frame_callback'  => array(Mage::helper('advr/callback'), 'percent'),
            'filter'          => false,
        );

        $columns += $this->getOrderTableColumns();

        $columns = $this->convertColumnsToSalesSource($columns);

        return $columns;
    }

    public function getGeoDimension()
    {
        if (!$this->getFilterData()->getGeoDimension()) {
            return 'country_id';
        }

        return $this->getFilterData()->getGeoDimension();
    }

    public function countryLinkCallBack($row)
    {
        $row->setGeoDimension('state');

        return Mage::helper('advr/callback')->rowUrl('*/*/*', $row, array('country_id', 'geo_dimension'));
    }

    public function stateLinkCallBack($row)
    {
        $row->setGeoDimension('place');

        return Mage::helper('advr/callback')->rowUrl('*/*/*', $row, array('country_id', 'state', 'geo_dimension'));
    }

    public function placeLinkCallBack($row)
    {
        $row->setGeoDimension('postcode');

        return Mage::helper('advr/callback')->rowUrl(
            '*/*/*',
            $row,
            array('country_id', 'state', 'place', 'geo_dimension')
        );
    }

    public function getShippingAddress()
    {
        if (!$this->getFilterData()->getShippingAddress()) {
            return 0;
        }

        return $this->getFilterData()->getShippingAddress();
    }
}
