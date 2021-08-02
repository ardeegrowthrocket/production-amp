<?php
class Growthrocket_Popular_Block_List extends Mage_Core_Block_Template
{

    protected $_collection;

    protected $_maxDisplay = 5;

    protected $_ymmParams;

    public function _construct()
    {

        parent::_construct();
    }

    protected function _prepareLayout()
    {
        $this->_ymmParams = unserialize($this->getRequest()->getParam('ymm_params'));
        return parent::_prepareLayout();
    }

    public function popularProductCollection($maxDisplay = 5)
    {

        if(!$this->_collection) {
            $request = Mage::app()->getRequest();
            $route = $request->getRouteName();
            $pathInfo = explode('/', trim($request->getPathInfo(), '/'));
            $store = Mage::app()->getStore();
            $model = '';
            $make = '';
            $year = '';

            if(isset($this->_ymmParams['make'])) {
                $make = $this->_ymmParams['make'];
            }

            if(isset($this->_ymmParams['model'])) {
                $model = $this->_ymmParams['model'];
            }

            if(isset($this->_ymmParams['year'])) {
                $year = $this->_ymmParams['year'];
            }

            $cacheId = 'popular_collection_' . $store->getId() . '-' . $make . $model . $year;


            if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
                $this->_collection = unserialize($data_to_be_cached);

            } else {

                $_mixes = Mage::getModel('hautopart/mix')->getCollection();

                foreach($this->_ymmParams as $key=> $value){
                    if($key != 'category'){
                        $_mixes->addFieldToFilter($key,$value);
                    }
                }
                $_mixes->getSelect()->group('product_id');
                $productIds = implode(',',$_mixes->getColumnValues('product_id'));

                $products = Mage::getResourceModel('reports/product_collection')
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter("status", Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->addAttributeToFilter("type_id", Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID)
                    ->addAttributeToFilter("entity_id", array('in' => $productIds))
                    ->setPageSize($maxDisplay);

                $products->addViewsCount();
                $products->setOrder('views', 'desc');

                $products->joinField('store_id', 'catalog_category_product_index', 'store_id', 'product_id=entity_id', '{{table}}.store_id = ' . $store->getId() . ' and {{table}}.store_id IS NOT NULL', 'inner');
                $products->getSelect()->where("e.entity_id IN ({$productIds})");

                foreach ($products as $product) {

                    $_product = Mage::getModel('catalog/product')->load($product->getId());
                    $this->_collection[] = array(
                        'product_id' => $product->getId(),
                        'name' => $_product->getName(),
                        'image' => (string)Mage::helper('catalog/image')->init($_product, 'small_image')->resize(209,198)->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE),
                        'link' => $product->getProductUrl(),
                        'price' => Mage::helper('core')->currency($_product->getFinalPrice(), true, false),
                        'part_number' => $_product->getData('amp_part_number')
                    );
                }

                Mage::app()->getCache()->save(serialize($this->_collection), $cacheId, array(), (60 * 60) * 24 );
            }

        }

        return $this->_collection;
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _getResources()
    {
        return  Mage::getSingleton('core/resource');
    }

    /**
     * @throws Zend_Date_Exception
     */
    protected function _getDate()
    {
        return new Zend_Date();
    }
}
