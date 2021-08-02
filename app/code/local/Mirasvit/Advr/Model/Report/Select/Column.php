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


class Mirasvit_Advr_Model_Report_Select_Column extends Varien_Object
{
    /**
     * @var Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
     */
    protected $filter;

    public function _construct()
    {
        $this->setGrid(new Varien_Object());

        return $this;
    }

    public function getFilterHtml()
    {
        return $this->getFilter()->getHtml();
    }

    protected function _getFilterByType()
    {
        $type = strtolower($this->getType());

        $classes = array(
            'datetime'   => 'adminhtml/widget_grid_column_filter_datetime',
            'date'       => 'adminhtml/widget_grid_column_filter_date',
            'range'      => 'adminhtml/widget_grid_column_filter_range',
            'number'     => 'adminhtml/widget_grid_column_filter_range',
            'currency'   => 'adminhtml/widget_grid_column_filter_range',
            'price'      => 'adminhtml/widget_grid_column_filter_price',
            'country'    => 'adminhtml/widget_grid_column_filter_country',
            'options'    => 'adminhtml/widget_grid_column_filter_select',
            'massaction' => 'adminhtml/widget_grid_column_filter_massaction',
            'checkbox'   => 'adminhtml/widget_grid_column_filter_checkbox',
            'radio'      => 'adminhtml/widget_grid_column_filter_radio',
            'store'      => 'adminhtml/widget_grid_column_filter_store',
            'theme'      => 'adminhtml/widget_grid_column_filter_theme',
            'array'      => 'advr/adminhtml_block_grid_column_filter_array',
        );

        if (isset($classes[$type])) {
            return $classes[$type];
        }

        return 'adminhtml/widget_grid_column_filter_text';
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
     */
    public function getFilter()
    {
        if (!$this->filter) {
            $filterClass = $this->_getFilterByType();

            $this->filter = Mage::app()->getLayout()->createBlock($filterClass)
                ->setColumn($this)
                ->setValue($this->getValue());
        }

        return $this->filter;
    }
}
