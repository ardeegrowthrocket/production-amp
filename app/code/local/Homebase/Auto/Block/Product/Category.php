<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/28/17
 * Time: 7:02 PM
 */

class Homebase_Auto_Block_Product_Category extends Mage_Catalog_Block_Product_Abstract{

    protected $_defaultToolbarBlock = 'hauto/product_listing_toolbar';

    protected $_productCollection;

    protected $_YmmParams;

    protected $_listingTitle = array();

    public function __construct()
    {
        $this->attributeMap = array(
            'year'  => 'auto_year',
            'make'  => 'auto_make',
            'model' => 'auto_model',
            'category'   => 'auto_type',
            'part'      => 'part_name'
        );

        $this->_YmmParams  = unserialize(Mage::app()->getRequest()->getParam('ymm_params'));

    }

    protected function _prepareLayout()
    {
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        $this->getListingTitle();
        if($breadcrumbs){
            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));

            if(isset($this->_listingTitle['category'])) {
                $categoryLabel = $this->_listingTitle['category'];
                $breadcrumbs->addCrumb('category', array(
                    'label' => $categoryLabel,
                    'title' => $categoryLabel,
                    'link'  => ''
                ));
            }
        }

        return parent::_prepareLayout();
    }

    public function getLoadedProductCollection()
    {
        return $this->_getProductList();
    }

    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    public function getToolbarBlock()
    {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }

    public function getMode()
    {
        return $this->getChild('toolbar')->getCurrentMode();
    }

    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }
    public function prepareSortableFieldsByCategory($category) {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($category->getAvailableSortByOptions());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = $category->getDefaultSortBy()) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    }
    protected function _getProductList(){

        if(is_null($this->_productCollection)){
            $resource = Mage::getSingleton('core/resource');
            $_store = Mage::app()->getStore();
            $params = unserialize($this->getRequest()->getParam('ymm_params'));
            $queryAttribute = $this->getRequest()->getParams();
            $attributeType = Mage::registry('category_custom_attribute_type');

            /** @var Mage_Catalog_Model_Resource_Product_Collection $production_collection */
            $production_collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', array('eq' => 1))
                ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID))
                ->addAttributeToFilter('auto_type',array('finset' => $params['category']));
            $production_collection->addWebsiteFilter($_store->getWebsite());

            $fitmentProducts = Mage::helper('hautopart')->getFitmentProducts();
            if(Mage::helper('hautopart')->isEnableRememberFitment() && !empty($fitmentProducts)){
                $production_collection->addFieldToFilter('entity_id',array('in' => $fitmentProducts));
            }

            $filterableAttributes = $this->_getFilterableAttributes($queryAttribute);
            if(!empty($filterableAttributes)) {
                foreach ($filterableAttributes as $attributeCode => $value) {

                    $value = str_replace('-and-', '&', $value);
                    if(isset($attributeType[$attributeCode]['type']) && $attributeType[$attributeCode]['type'] == 'multiselect') {
                        $valueToArray = explode(',', $value);
                        $querycombination = array();
                        foreach ($valueToArray as $value) {
                            $querycombination[] = array('attribute'=> $attributeCode, 'finset'=> $value);
                        }
                        if(!empty($querycombination)){
                            $production_collection->addAttributeToFilter($querycombination);
                        }

                    }else {
                        $valueToArray = explode(',', $value);
                        $production_collection->addAttributeToFilter($attributeCode, array('in' => $valueToArray));
                    }

                }
            }

            $productIds = Mage::registry('fitment_product_ids');
            if(!empty($productIds)){
                $production_collection->addAttributeToFilter('entity_id', array('in' => $productIds));
            }

            $this->_productCollection = $production_collection;

        }

        return $this->_productCollection;
    }

    protected function _getFilterableAttributes($queryAttribute)
    {
        $allowedAttributes = Mage::registry('category_custom_attribute');
        $getResult = array();
        if(!empty($queryAttribute)) {
            $getResult = array_filter(array_intersect_key($queryAttribute,$allowedAttributes));
        }

       return $getResult;
    }

    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        $collection = $this->getLoadedProductCollection();

        // use sortable parameters
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }

        $toolbar->setCollection($collection);


        $this->setChild('toolbar',$toolbar);

        return parent::_beforeToHtml();
    }


    public function getListingTitle()
    {
        if(empty($this->_listingTitle)) {
            $params = $this->_YmmParams;
            foreach($params as $key=>$value){
                if($key !== 'part' && $key !== 'year'){
                    $this->_listingTitle[$key] = Mage::helper('hauto/path')->getRawOptionText($key,$value);
                }
            }
            if(array_key_exists('year',$params)){
                $yearLabel = Mage::helper('hauto/path')->getRawOptionText('year',$params['year']);
                array_unshift($this->_listingTitle,$yearLabel);
            }
        }

        return implode(' ', $this->_listingTitle);
    }

}