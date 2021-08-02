<?php
class Growthrocket_Recommended_Block_Recommended extends Mage_Core_Block_Template
{
    protected $_limit;

    protected $_params;

    protected $_collection;

    protected $_recommendedProducts;

    protected function _prepareLayout()
    {
        $this->_limit =  5;
        $this->_params = unserialize($this->getRequest()->getParam('ymm_params'));
        $this->_getCollection();
        return parent::_prepareLayout();
    }

    protected function _getCollection()
    {

        if(!$this->_collection) {

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('amp_part_number', 'small_image', 'price','name','custom_url_key'))
                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addWebsiteFilter($this->_getStore()->getWebsite())
                ->addFinalPrice()
                ->setPageSize($this->_limit);


            $collection->getSelect()->joinLeft(
                array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                "e.entity_id = aggregation.product_id", array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )->order('sold_quantity DESC')->group('e.entity_id');

            $collection->getSelect()->joinLeft(
                array("auto" => $this->_getResources()->getTableName('hautopart/combination_list')),
                "e.entity_id = auto.product_id", array('auto_make' => 'auto.make'));
            $collection->getSelect()->where("auto.make={$this->_params['make']} and auto.model={$this->_params['model']} and auto.year={$this->_params['year']}");

            if($collection){
                foreach ($collection as $product){
                    $this->_recommendedProducts[$product->getId()] = array(
                        'product_id' => $product->getId(),
                        'name' => $product->getName(),
                        'image' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(209,198)->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE),
                        'link' => $product->getProductUrl(),
                        'price' => Mage::helper('core')->currency($product->getFinalPrice(), true, false),
                        'part_number' => $product->getData('amp_part_number')
                    );
                }
            }

            $this->_collection = $collection;
        }

        return $this->_collection;
    }

    /**
     * @return mixed
     */
    public function getRecommendedProducts()
    {
        return $this->_recommendedProducts;
    }

    /**
     * @return Mage_Core_Model_Store
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _getResources()
    {
        return  Mage::getSingleton('core/resource');
    }

}