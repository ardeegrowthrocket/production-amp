<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/5/17
 * Time: 12:27 AM
 */

class Homebase_Autopart_Block_Display_Category extends Mage_Catalog_Block_Product_Abstract{

    /** @var Homebase_Autopart_Helper_Parser  $_helper */
    protected $_helper;
    /** @var Homebase_Autopart_Helper_Data $_dataHelper */
    protected $_dataHelper;

    protected $_defaultToolbarBlock = 'hautopart/display_list_toolbar';

    protected $_productCollection;

    public function _construct(){
        parent::_construct();
        $this->_helper = Mage::helper('hautopart/parser');
        $this->_dataHelper = Mage::helper('hautopart');
    }
    protected function _prepareLayout(){
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $size = count($params);
        $labelArray = array();
        $name = "";
        $ctr = 0;
        if($breadcrumbs){
            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));

            foreach($params as $ndx=> $value){
                $ctr++;
                $label = $this->_helper->getLabel($value);

                if($ndx == 'model') {
                    $name = $this->_helper->getLabel($value,'name');
                }else{
                    $name = $label;
                }

                $labelArray[$ndx] = $name;
                if($ctr == $size){
                    $label = $this->_dataHelper->getOptionValue($value);
                }

                if($ndx == 'model') {
                    $label = "{$labelArray['make']} {$labelArray['model']}";
                }

                $breadcrumbs->addCrumb('ymm-' . $ndx, array(
                    'label' => strtoupper($label),
                    'title' => $label,
                    'link'  => (($ctr < $size) ? $this->_helper->getLink($name,$ndx) : '')
                ));
            }
        }
        return parent::_prepareLayout();
    }

    protected function _getProductList(){
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
        $_mixes = Mage::getModel('hautopart/mix')->getCollection();

        foreach($params as $key=> $value){
            if($key != 'category'){
                $_mixes->addFieldToFilter($key,$value);
            }
        }
        $_mixes->getSelect()->group('product_id');
        $productIds = $_mixes->getColumnValues('product_id');
        $universalProduct = Mage::helper('hautopart')->getUniversalProducts();
        $productIds = array_merge($productIds, $universalProduct);
        $queryAttribute = $this->getRequest()->getParams();
        $attributeType = Mage::registry('category_custom_attribute_type');
        if(is_null($this->_productCollection)){
            $layer = $this->getLayer();
            /** @var Mage_Catalog_Model_Resource_Product_Collection $product_collection */
            $product_collection = Mage::getModel('catalog/product')->getCollection();

            $product_collection->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID))
                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addFieldToFilter('entity_id',$productIds);

            if(!empty($params['category'])) {
                $product_collection->addAttributeToFilter('auto_type',array('finset' => $params['category']));
            }


            $fitmentProducts = Mage::helper('hautopart')->getFitmentProducts();
            $subaruGear = 3510;
            if(Mage::helper('hautopart')->isEnableRememberFitment() && !empty($fitmentProducts) && $params['category'] != $subaruGear){
                $product_collection->addFieldToFilter('entity_id',array('in' => $fitmentProducts));
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
                            $product_collection->addAttributeToFilter($querycombination);
                        }

                    }else {
                        $valueToArray = explode(',', $value);
                        $product_collection->addAttributeToFilter($attributeCode, array('in' => $valueToArray));
                    }

                }
            }

            $product_collection->addFinalPrice();
            $this->_productCollection = $product_collection;
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
        }
        Mage::Helper('hauto')->redirectSingleProduct($this->_productCollection);
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

    public function getLoadedProductCollection()
    {
        return $this->_getProductList();
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

        return parent::_beforeToHtml(); // TODO: Change the autogenerated stub
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
    public function getListingTitle(){
        $_request = $this->getRequest();
        $params = unserialize($_request->getParam('ymm_params'));

        $_request = $this->getRequest();
        $params = unserialize($_request->getParam('ymm_params'));

        $ymm = array();
        foreach($params as $key=>$value){
            if($key !== 'part' && $key !== 'year'){
                $optionId = $value;
                $value = Mage::helper('hauto/path')->getRawOptionText($key,$value);
                if($key == 'category'){
                    $value = '<span>' . $value . '</span>';
                }
                $ymm[$key] = $value;
            }

            if($key == 'model') {
                $ymm['model'] = Mage::helper('hautopart/parser')->getLabel($optionId,'name');
            }
        }
        if(array_key_exists('year',$params)){
            $yearLabel = Mage::helper('hauto/path')->getRawOptionText('year',$params['year']);
            array_unshift($ymm,$yearLabel);
        }

        if(isset($params['part'])) {
            $ymm['part'] = $params['part'];
        }

        return implode(' ', $ymm);
    }
}