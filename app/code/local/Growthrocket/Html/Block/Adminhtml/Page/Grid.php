<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26/09/2018
 * Time: 9:49 PM
 */

class Growthrocket_Html_Block_Adminhtml_Page_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    public function _construct(){
        parent::_construct();
    }

    protected function _getCollectionClass(){
        return 'grhtml/page_collection';
    }
    protected function _prepareCollection(){
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    protected function _prepareColumns(){
        $this->addColumn('id', array(
            'header'    => Mage::helper('grhtml')->__('ID'),
            'width'     => '10px',
            'index'     => 'id'
        ));
        $this->addColumn('module', array(
            'header'    => Mage::helper('grhtml')->__('Module'),
            'width'     => '30px',
            'index'     => 'module'
        ));
        $this->addColumn('url', array(
            'header'    => Mage::helper('grhtml')->__('Request String'),
            'index'     => 'url'
        ));
        $this->addColumn('title', array(
            'header'    => Mage::helper('grhtml')->__('Page Title'),

            'index'     => 'title'
        ));
        $this->addColumn('meta_desc', array(
            'header'    => Mage::helper('grhtml')->__('Meta Description'),
            'index'     => 'meta_desc'
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('grhtml')->__('Store ID'),
                'width' => '10px',
                'index' => 'store_id',
                'type' => 'store'
            ));
        }
    }
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}