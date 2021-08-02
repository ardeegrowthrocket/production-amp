<?php

class Homebase_Autopart_Block_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{

    /**
     * Override getProductUrl()
     * Retrieve URL to item Product
     *
     * @return string
     */
    public function getProductUrl()
    {

        if (!is_null($this->_productUrl)) {
            return $this->_productUrl;
        }
        if ($this->getItem()->getRedirectUrl()) {
            return $this->getItem()->getRedirectUrl();
        }

        $product = $this->getProduct();
        if(!empty($product->getCustomUrlKey())){
            $_helper = Mage::helper('hautopart');
            return strtolower($_helper->getSkuPath($product->getCustomUrlKey()));
        }

        $option  = $this->getItem()->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }

}