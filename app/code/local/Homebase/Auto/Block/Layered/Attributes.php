<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/28/17
 * Time: 7:47 PM
 */

class Homebase_Auto_Block_Layered_Attributes extends Mage_Core_Block_Template{

    const PARTNAMECODE = 'part_name';

    protected $_params;

    protected $_query;

    protected $_collection;

    protected $_store;

    protected $_attributes;

    protected $_attributeCollection;

    protected $_autoMake;

    protected $_ymmFilter;

    protected function _prepareLayout()
    {
        $this->_params = unserialize($this->getRequest()->getParam('ymm_params'));
        $this->_store =  Mage::app()->getStore();

        $this->_attributes = array(self::PARTNAMECODE => 'Part Names');
        $this->_query = $this->getRequest()->getParams();

        $attr = array();
        $filterableAttribute = array();
        $attributes = Mage::getSingleton('catalog/layer')->getFilterableAttributes();
        foreach ($attributes as $attribute) {
            $attr[$attribute->getAttributeCode()]['type'] = $attribute->getBackendType();
            $attr[$attribute->getAttributeCode()]['label'] = $attribute->getFrontendLabel();
            $attr[$attribute->getAttributeCode()]['type'] = $attribute->getFrontendInput();

            $filterableAttribute[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        unset($filterableAttribute['price']);
        $this->_attributes = array_merge($this->_attributes, $filterableAttribute);
        $this->_ymmFilter = Mage::helper('hauto')->getYmmFilter();
        Mage::register('category_custom_attribute', $this->_attributes);
        Mage::register('category_custom_attribute_type', $attr);
        Mage::register('fitment_product_ids', $this->_ymmFilter['product_id']);

    }

    public function getMakeNavigation()
    {
        $code = 'make';
        $params = array();
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $currentUrl = strtok($currentUrl, "?");
        $makeNavigation = $this->_ymmFilter;
        if(isset($this->_query['make']) && isset($this->_query['model']) && isset($this->_query['year'])){

            $ymmArray = array('year','make','model');
            $title  = "";
            foreach ($ymmArray as $key) {
                if(isset($makeNavigation[$key])){
                    $title .= ' ' .  end($makeNavigation[$key]);
                }
            }

            return array(
                'ymm' => trim($title),
                'reset' => $currentUrl
            );
        }
        elseif(isset($this->_query['make']) && isset($this->_query['model'])){
            $code = 'year';
            $params['make']  = $this->_query['make'];
            $params['model']  = $this->_query['model'];

        }elseif(isset($this->_query['make'])){
            $code = 'model';
            $params['make']  = $this->_query['make'];
        }

        $makeArray = array();
        foreach ($makeNavigation[$code] as $key => $label){
            $params[$code] = $key;
            $makeArray[$code][$key] = array(
                'label'=> $label,
                'url' => Mage::helper('core/url')->addRequestParam($currentUrl, $params)
            );
        }

        return $makeArray;
    }

    protected function _beforeToHtml()
    {
        if(!$this->_collection) {
            $production_collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array_keys($this->_attributes))
                ->addAttributeToFilter('status', array('eq' => 1))
                ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID))
                ->addAttributeToFilter('auto_type',array('finset' => $this->_params['category']));

            if(!empty($this->_ymmFilter['product_id'])){
                $production_collection->addAttributeToFilter('entity_id', array('in' => $this->_ymmFilter['product_id']));
            }

            $fitmentProducts = Mage::helper('hautopart')->getFitmentProducts();
            if(Mage::helper('hautopart')->isEnableRememberFitment() && !empty($fitmentProducts)){
                $production_collection->addFieldToFilter('entity_id',array('in' => $fitmentProducts));
            }

            $production_collection->addWebsiteFilter($this->_store->getWebsite());

            foreach ($this->_attributes as $attributeCode => $attribute) {
                foreach ($production_collection as $product) {

                    if($attributeCode == self::PARTNAMECODE) {
                        $label = trim($product->getData($attributeCode));
                        $id = trim($product->getData($attributeCode));
                        $selectedId = $label;
                    }else {
                        $label = trim($product->getAttributeText($attributeCode));
                        $id = trim($product->getData($attributeCode));
                        $selectedId = $id;
                    }

                    if(!empty($label)) {

                        if(is_array($label)){
                            $ids = explode(",", $id);
                            foreach ($label as $key => $title) {
                                $id = $ids[$key];
                                $selectedId = $id;
                                $this->_attributeCollection[$attribute][$id] = $this->_setLayerData($attributeCode, $title, $id,$selectedId);
                            }
                        }else {
                            $this->_attributeCollection[$attribute][$id] = $this->_setLayerData($attributeCode, $label, $id,$selectedId);
                        }

                    }
                }

                if(!empty($this->_attributeCollection[$attribute])){
                    ksort($this->_attributeCollection[$attribute]);
                }

            }

            $this->_collection = $production_collection;
        }
        return $this->_collection;
    }

    protected function _setLayerData($attributeCode, $label, $id,$selectedId)
    {
        $param = '';
        if (array_key_exists($attributeCode, $this->_query)) {
            $param = $this->_query[$attributeCode];
        }

        $paramsToArray = explode(',', $param);
        if(!empty($paramsToArray)){
            $selectedId = str_replace('&', '-and-', $selectedId);
            $isSelected = in_array(strtolower($selectedId), explode(',', $param)) ? true : false;
        }else {
            $isSelected = false;
        }

        return array(
            'url' => $this->getParamUrl($attributeCode, $id, $isSelected),
            'label' => $label,
            'is_selected' => $isSelected
        );
    }

    protected function getParamUrl($key, $value, $isSelected)
    {
        $value = str_replace('&', '-and-', $value);
        unset($this->_query['ymm_params']);
        $value = strtolower($value);
        $params = $this->_query;
        if(array_key_exists($key,$this->_query)) {
            foreach ($this->_query as $indx => $val) {
                if($indx == $key) {
                    $queryValue = explode(',', $val);
                    $queryValue = array_merge($queryValue, array($value));
                    $queryValue = array_unique($queryValue);
                    foreach ($queryValue as $item) {
                        if($item == $value && $isSelected) {
                            $queryValue = array_diff($queryValue, [$value]);
                        }
                    }
                    $params[$indx] = implode(',',array_filter($queryValue));
                    break;
                }else {
                    $params =  array_merge($params,array($key => $value));
                }
            }

        }else {
            $params =  array_merge($params,array($key => $value));
        }


        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $currentUrl = strtok($currentUrl, "?");
        $params = array_filter($params);
        $currentUrl = Mage::helper('core/url')->addRequestParam($currentUrl, $params);

        return $currentUrl;
    }

    public function clearFilter($attributeCode)
    {
        $queryString = $this->_query;
        $key = array_search($attributeCode, $this->_attributes);

        if(isset($queryString[$key])) {
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $currentUrl = strtok($currentUrl, "?");
            unset($queryString[$key]);
            $queryString = array_filter($queryString);
            $currentUrl = Mage::helper('core/url')->addRequestParam($currentUrl, $queryString);

            return $currentUrl;
        }else {
            return false;
        }

    }

    public function getAttributeCollection()
    {
        return $this->_attributeCollection;
    }



    public function getList()
    {
        return false;
    }

    public function getLayerTitle()
    {
        return 'Make';
    }
}