<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/12/18
 * Time: 11:08 AM
 */

class Growthrocket_Schema_Block_Product extends Growthrocket_Schema_Block_Schema{

    protected function getSchema(){
        $_product = Mage::registry('current_product');
        $data = array(
            '@context' => self::CONTEXT,
            '@type' => 'Product',
            'name'  => $_product->getName(),
            'url' => $_product->getProductUrl(),
            'description' => $this->stripTags($_product->getDescription()),
            'image' =>  $_product->getImageUrl(),
            'sku'   => $_product->getSku(),
            'offers' => array(
                '@type' => 'Offer',
                'availability' => 'http://schema.org/OnlineOnly',
                'price' => str_replace(',','',number_format($_product->getFinalPrice(),2)),
                'priceCurrency' => $this->__getCurrencyCode(),
                'url' => $_product->getProductUrl()
            ),
        );
        return json_encode($data);
    }
    private function __getCurrencyCode(){
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }
}