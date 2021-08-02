<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:20 PM
 */

class Growthrocket_Content_Block_Adminhtml_Page_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    public function _construct(){
        parent::_construct();
    }

    protected function _getCollectionClass(){
        return 'grcontent/page_collection';
    }

    protected function _prepareCollection(){
        $collection = Mage::getResourceModel($this->_getCollectionClass());

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(){
        $this->addColumn('id', array(
            'header'    => Mage::helper('grcontent')->__('ID'),
            'width'     => '10px',
            'index'     => 'id'
        ));
        $this->addColumn('type', array(
            'header'    => Mage::helper('grcontent')->__('Type'),
            'width'     => '180px',
            'index'     => 'type'
        ));
        $this->addColumn('url', array(
            'header'    => Mage::helper('grcontent')->__('Request URL'),
            'index'     => 'url'
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('grcontent')->__('Store ID'),
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