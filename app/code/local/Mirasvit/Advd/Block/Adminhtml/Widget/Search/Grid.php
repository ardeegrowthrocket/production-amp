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



class Mirasvit_Advd_Block_Adminhtml_Widget_Search_Grid extends Mirasvit_Advd_Block_Adminhtml_Widget_Abstract_Grid
{
    public function getGroup()
    {
        return 'Search';
    }

    public function getName()
    {
        return 'Last Search Terms';
    }

    /**
     * Return columns used in grid.
     *
     * @param bool|true $forGrid - if false add column used for ordering
     *
     * @return array
     */
    public function getColumns($forGrid = true)
    {
        $columns = array(
            array(
                'label' => Mage::helper('advd')->__('Search Term'),
                'value' => 'query_text',
                'renderer' => 'adminhtml/dashboard_searches_renderer_searchquery',
                'type' => 'text',
            ),
            array(
                'label' => Mage::helper('advd')->__('Results'),
                'value' => 'num_results',
                'type' => 'number',
            ),
            array(
                'label' => Mage::helper('advd')->__('Number of Uses'),
                'value' => 'popularity',
                'type' => 'number',
            ),
        );

        if (!$forGrid) {
            $columns[] = array(
                'label' => Mage::helper('advd')->__('Recent Use'),
                'value' => 'updated_at',
            );
        }

        return $columns;
    }

    public function prepareOptions()
    {
        $this->form->addField(
            'limit',
            'text',
            array(
                'name' => 'limit',
                'label' => Mage::helper('advr')->__('Number Of Search Terms'),
                'value' => $this->getParam('limit', 5),
            )
        );

        $this->form->addField(
            'sort_by',
            'select',
            array(
                'name' => 'sort_by',
                'label' => Mage::helper('advr')->__('Sort By Field'),
                'values' => $this->getColumns(false),
                'value' => $this->getParam('sort_by', 'updated_at'),
            )
        );

        $this->form->addField(
            'sort_dir',
            'select',
            array(
                'name' => 'sort_dir',
                'label' => Mage::helper('advr')->__('Sort Direction'),
                'values' => array('asc' => 'ASC', 'desc' => 'DESC'),
                'value' => $this->getParam('sort_dir', 'desc'),
            )
        );

        return $this;
    }

    protected function _prepareCollection($grid)
    {
        $collection = Mage::getModel('catalogsearch/query')
            ->getResourceCollection();
        $collection->setOrder($this->getParam('sort_by', 'updated_at'), $this->getParam('sort_dir', 'desc'));

        $grid->setCollection($collection);

        return $this;
    }

    protected function _prepareColumns($grid)
    {
        foreach ($this->getColumns() as $column) {
            $column['header'] = $column['label'];
            $column['index'] = $column['value'];
            $column['sortable'] = false;
            $grid->addColumn($column['value'], $column);
        }

        $grid->setFilterVisibility(false);
        $grid->setPagerVisibility(false);
        $grid->setDefaultLimit($this->getParam('limit', 5));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/catalog_search/edit', array('id' => $row->getId()));
    }
}
