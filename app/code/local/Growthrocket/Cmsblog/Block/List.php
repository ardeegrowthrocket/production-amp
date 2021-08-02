<?php
class Growthrocket_Cmsblog_Block_List extends Mage_Core_Block_Template
{

    protected $_collection;

    /**
     * Growthrocket_Cmsblog_Block_List constructor.
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @return object
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function blogCollection()
    {
        if(!$this->_collection) {
            $collection = Mage::getModel('cmsblog/cmsblog')->getCollection();
            $collection->addFieldToFilter('is_active', 1);
            $collection->addFieldToFilter('store_ids', Mage::app()->getStore()->getId());

            $this->_collection = $collection;
        }
        return $this->_collection;
    }

    /**
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {

        parent::_prepareLayout();
        $toolbar = $this->getLayout()->createBlock('cmsblog/toolbar');
        $collection = $this->blogCollection();
        $toolbar->setAvailableOrders(array('created_date'=> 'Created Date','title'=>'Title'));
        $toolbar->setDefaultOrder('created_date');
        $toolbar->setDefaultDirection("desc");
        $toolbar->setCollection($collection);
        $this->setChild('toolbar', $toolbar);
        return $this;
    }


    /**
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }
}