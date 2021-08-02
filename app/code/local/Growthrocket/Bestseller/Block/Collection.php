<?php   
class   Growthrocket_Bestseller_Block_Collection extends Mage_Core_Block_Template
{
    /** @var array  */
    protected $_collection = array();

    /** @var  */
    protected $_fromDate;

    /** @var  */
    protected $_toData;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->__('Bestsellers');
    }

    /**
     * @return array|mixed
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Cache_Exception
     * @throws Zend_Date_Exception
     */
    public function getCollection($dimension = array(209,198))
    {
        $storeId = $this->_getStoreId();
        $cacheId = 'bestseller_collection_' . $storeId;

        $toDate = $this->_getDate()->setDay(1)->getDate()->get('Y-MM-dd');
        $fromDate = $this->_getDate()->subMonth(1)->getDate()->get('Y-MM-dd');

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $this->_collection = unserialize($data_to_be_cached);

        } else {

            $collection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect(array('name', 'amp_part_number', 'small_image','sku','auto_type','custom_url_key'))
                ->addStoreFilter()
                ->addPriceData()
                ->addTaxPercents()
                ->addUrlRewrite()
                ->setPageSize(10);

            $collection->getSelect()
                ->joinLeft(
                    array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                    "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId} AND aggregation.period BETWEEN '{$fromDate}' AND '{$toDate}'",
                    array('SUM(aggregation.qty_ordered) AS sold_quantity')
                )
                ->group('e.entity_id')
                ->order(array('sold_quantity DESC', 'e.created_at'))->limit(10);


            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
            $position = 1;

            foreach ($collection as $product) {

                $varDataLayer = [
                    "name" => $product->getName(),
                    "category" => $product->getAttributeText('auto_type'),
                    "brand" => mage::Helper('growthrocket_gtm')->getDefaultBrand(),
                    "id" => $product->getSku(),
                    "price" => Mage::getModel('directory/currency')->format($product->getFinalPrice(), array('display'=>Zend_Currency::NO_SYMBOL), false),
                    "list" => "Bestseller Products",
                    "url" => $product->getProductUrl(),
                    "position" => $position++
                ];

                $this->_collection[] = array(
                    'product_id' => $product->getId(),
                    'name' => $product->getName(),
                    'image' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize($dimension[0], $dimension[1])->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE),
                    'link' => $product->getProductUrl(),
                    'price' => $product->getFinalPrice(),
                    'part_number' => $product->getData('amp_part_number'),
                    'ga_tracking' => $varDataLayer
                );
            }

            Mage::app()->getCache()->save(serialize($this->_collection), $cacheId, array(), (60 * 60) * 24 );
        }


        return $this->_collection;
    }

    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Cache_Exception
     * @throws Zend_Date_Exception
     */
    public function getPdpbestSeller()
    {
        $bestsellerCollection = array();
        $storeId = $this->_getStoreId();

        $toDate = $this->_getDate()->setDay(1)->getDate()->get('Y-MM-dd');
        $fromDate = $this->_getDate()->subMonth(1)->getDate()->get('Y-MM-dd');

        $cacheId = 'bestseller_collection_pdp_' . $storeId;
        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $bestsellerCollection = unserialize($data_to_be_cached);

        } else {

            $collection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect(array('name', 'amp_part_number', 'small_image','custom_url_key'))
                ->addTaxPercents()
                ->addPriceData()
                ->addUrlRewrite()
                ->setPageSize(5);

            $collection->getSelect()
                ->joinLeft(
                    array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                    "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId} AND aggregation.period BETWEEN '{$fromDate}' AND '{$toDate}'",
                    array('SUM(aggregation.qty_ordered) AS sold_quantity')
                )
                ->group('e.entity_id')
                ->order(array('sold_quantity DESC', 'e.created_at'))->limit(5);


            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            $position = 1;

            foreach ($collection as $product) {

                $varDataLayer = [
                    "name" => $product->getName(),
                    "category" => $product->getAttributeText('auto_type'),
                    "brand" => mage::Helper('growthrocket_gtm')->getDefaultBrand(),
                    "id" => $product->getSku(),
                    "price" => Mage::getModel('directory/currency')->format($product->getFinalPrice(), array('display'=>Zend_Currency::NO_SYMBOL), false),
                    "list" => "Bestseller Products",
                    "url" => $product->getProductUrl(),
                    "position" => $position++
                ];

                $bestsellerCollection[] = array(
                    'product_id' => $product->getId(),
                    'name' => $product->getName(),
                    'image' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(224, 221)->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE),
                    'link' => $product->getProductUrl(),
                    'price' => $product->getFinalPrice(),
                    'part_number' => $product->getData('amp_part_number'),
                    'ga_tracking' => $varDataLayer
                );
            }
            Mage::app()->getCache()->save(serialize($bestsellerCollection), $cacheId, array(), (60 * 60) * 24 );
        }

        return $bestsellerCollection;
    }

    /**
     * @return int
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getStoreId()
    {
        return (int) Mage::app()->getStore()->getId();
    }


    /**
     * @throws Zend_Date_Exception
     */
    protected function _getDate()
    {
        return new Zend_Date();
    }

}