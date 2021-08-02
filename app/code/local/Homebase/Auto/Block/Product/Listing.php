<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/28/17
 * Time: 7:02 PM
 */

class Homebase_Auto_Block_Product_Listing extends Mage_Catalog_Block_Product_Abstract{

    protected $_defaultToolbarBlock = 'hauto/product_listing_toolbar';
    protected $_productCollection;

    public function __construct()
    {
        $this->attributeMap = array(
            'year'  => 'auto_year',
            'make'  => 'auto_make',
            'model' => 'auto_model',
            'category'   => 'auto_type',
            'part'      => 'part_name'
        );

    }

    public function getLoadedProductCollection(){
        return $this->_getProductList();
    }
    public function getToolbarHtml(){
        return $this->getChildHtml('toolbar');
    }
    public function getToolbarBlock(){
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }
    public function getMode(){
        return $this->getChild('toolbar')->getCurrentMode();
    }
    public function getLayer(){
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
        $helper = Mage::helper('hautopart');
        $_store = Mage::app()->getStore();
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        if(is_null($this->_productCollection)){
            $layer = $this->getLayer();
            /** @var Mage_Catalog_Model_Resource_Product_Collection $production_collection */
            $production_collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', array('eq' => 1))
                ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID))
                ->addFinalPrice();
            $production_collection->addWebsiteFilter($_store->getWebsite());
            $fitment = array();
            foreach($params as $ndx => $value){
                if($ndx == 'part'){
                    $production_collection->addAttributeToFilter('part_name',array('eq' => $value));
                }else{
                    $fitment[] = array(
                        $ndx => $value
                    );
                }
            }
          
            $fitmentProducts = $helper->getFitmentProducts();
            if($helper->isEnableRememberFitment() && !empty($fitmentProducts) && $params['part'] != 'Subaru Gear'){
                $production_collection->addFieldToFilter('entity_id',array('in' => $fitmentProducts));
            }

            if(count($fitment) > 0){
                $dataHelper = Mage::helper('hauto');
                $productIds = $dataHelper->fetchProductEntityId($fitment);
                if(count($productIds) > 0){
                    $production_collection->addFieldToFilter('entity_id',array('in' => $productIds));
                }
            }
            $this->_productCollection = $production_collection;
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
        }
        Mage::Helper('hauto')->redirectSingleProduct($this->_productCollection);
        return $this->_productCollection;
    }
    public function getCollection(){
        $collection = null;
        $_store = Mage::app()->getStore();
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        if(is_null($this->_productCollection)){
            $layer = $this->getLayer();
            /** @var Mage_Catalog_Model_Resource_Product_Collection $production_collection */
            $production_collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', array('eq' => 1))
                ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID));
            $production_collection->addWebsiteFilter($_store->getWebsite());
            $fitment = array();
            foreach($params as $ndx => $value){
                if($ndx == 'part'){
                    $production_collection->addAttributeToFilter('part_name',array('eq' => $value));
                }else{
                    $fitment[] = array(
                        $ndx => $value
                    );
                }
            }

            if(count($fitment) > 0){
                $dataHelper = Mage::helper('hauto');
                $productIds = $dataHelper->fetchProductEntityId($fitment);
                if(count($productIds) > 0){
                    $production_collection->addFieldToFilter('entity_id',array('in' => $productIds));
                }
            }
            $collection = $production_collection;
//            $this->_productCollection = $production_collection;
        }
        return $collection;
    }
    protected function _beforeToHtml(){
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

    public function getListingTitle(){
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $this->getRequest();
        $params = unserialize($_request->getParam('ymm_params'));
        $_helper = Mage::helper('hautopart/parser');
        $ymm = array();
        foreach($params as $key=>$value){
            if($key !== 'part' && $key !== 'year'){
                $ymm[$key] = $_helper->getLabel($value);
            }

            if($key == 'model') {
                $ymm['model'] = $_helper->getLabel($value,'name');
            }
        }
        if(array_key_exists('year',$params)){
            $yearLabel = $_helper->getLabel($params['year']);
            array_unshift($ymm,$yearLabel);
        }

        $ymm['part'] = $params['part'];
        return implode(' ', $ymm);
    }

}