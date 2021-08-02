<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/10/17
 * Time: 9:19 PM
 */

class Homebase_Sitemap_Block_Adminhtml_Multimap_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    public function _construct(){
        parent::_construct();
    }
    protected function _getCollectionClass(){
        return 'hsitemap/multimap_collection';
    }
    protected function _prepareCollection(){
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => Mage::helper('hsitemap')->__('ID'),
            'width'     => '50px',
            'index'     => 'id'
        ));

        $this->addColumn('filename', array(
            'header'    => Mage::helper('hsitemap')->__('Filename'),
            'index'     => 'filename'
        ));

        $this->addColumn('path', array(
            'header'    => Mage::helper('hsitemap')->__('Path'),
            'index'     => 'path'
        ));

        $this->addColumn('link', array(
            'header'    => Mage::helper('hsitemap')->__('Link for Google'),
            'index'     => 'concat(path, filename)',
            'renderer'  => 'hsitemap/adminhtml_multimap_grid_renderer_link',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('hsitemap')->__('Store View'),
                'index'     => 'store_id',
                'type'      => 'store',
            ));
        }

        $this->addColumn('action', array(
            'header'   => Mage::helper('hsitemap')->__('Action'),
            'filter'   => false,
            'sortable' => false,
            'width'    => '100',
            'renderer' => 'hsitemap/adminhtml_multimap_grid_renderer_action'
        ));

        return parent::_prepareColumns();
    }
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}