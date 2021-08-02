<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/18/17
 * Time: 11:42 PM
 */

class Homebase_Autopart_Model_Product_Url extends Mage_Catalog_Model_Product_Url{
    public function getUrl(Mage_Catalog_Model_Product $product, $params = array())
    {
        $_helper = Mage::helper('hautopart');
        $url = $product->getData('url');
        if (!empty($url)) {
            if($product instanceof Homebase_Autopart_Model_Product){
                return $_helper->getSkuPath($product->getSku());
            }else{
                return $url;
            }
        }

        $requestPath = $product->getData('request_path');
        if (empty($requestPath)) {
            $requestPath = $this->_getRequestPath($product, $this->_getCategoryIdForUrl($product, $params));
            $product->setRequestPath($requestPath);
        }

        if (isset($params['_store'])) {
            $storeId = $this->_getStoreId($params['_store']);
        } else {
            $storeId = $product->getStoreId();
        }

        if ($storeId != $this->_getStoreId()) {
            $params['_store_to_url'] = true;
        }

        // reset cached URL instance GET query params
        if (!isset($params['_query'])) {
            $params['_query'] = array();
        }

        $this->getUrlInstance()->setStore($storeId);
        $productUrl = $this->_getProductUrl($product, $requestPath, $params);
        $product->setData('url', $productUrl);

        if($product->getTypeId() == Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID){
            return $_helper->getSkuPath($product->getSku());
        }else{
            return $product->getData('url');
        }
    }
}