<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 12:03 AM
 */
class Growthrocket_Content_Block_Adminhtml_Content_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    public function _construct(){
        parent::_construct();
    }
    protected function _getCollectionClass(){
        return 'grcontent/content_collection';
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
        $this->addColumn('name', array(
            'header'    => Mage::helper('grcontent')->__('Content Name'),
            'width'     => '180px',
            'index'     => 'name'
        ));
        $this->addColumn('content', array(
            'header'    => Mage::helper('grcontent')->__('Content'),
            'index'     => 'content'
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