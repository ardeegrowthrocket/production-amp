<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/12/18
 * Time: 2:40 PM
 */


class Growthrocket_Schema_Block_Listing extends Growthrocket_Schema_Block_Schema{
    protected function getSchema(){
        $helper = Mage::helper('grschema');
        $products = array();
        $part = $this->getFitmentParam('part');
        $make = $this->getFitmentParam('make');
        $model = $this->getFitmentParam('model');
        $year = $this->getFitmentParam('year');
        $fitment = $this->getFitmentParam(array('make','model', 'year'));
        /** @var Mage_Catalog_Model_Resource_Product_Collection $production_collection */
        $production_collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', array('eq' => 1))
            ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID));
        $production_collection->addWebsiteFilter($this->getWebsite());
        $production_collection->addFinalPrice();
        if(!is_null($part)){
            $production_collection->addAttributeToFilter('part_name',array('eq' => $part));
        }
        if(!is_null($fitment) && !empty($fitment)){
            $dataHelper = Mage::helper('hauto');
            $fitments = array();
            foreach($fitment as $idx => $item){
                $fitments[] = array(
                    $idx => $item
                );
            }
            $productIds = $dataHelper->fetchProductEntityId($fitments);
            if(count($productIds) > 0){
                $production_collection->addFieldToFilter('entity_id',array('in' => $productIds));
            }
        }
        foreach($production_collection as $item){
$_id = $item->getId(); 

$cbpGroup = Mage::helper('aitcbp')->getGroup($item->getCbpGroup());
$cbpPrice =  Mage::helper('aitcbp')->getPrice($item, $cbpGroup);   
$cbpPrice = number_format($cbpPrice,2);
      
            $itemp = array(
                '@context' => 'https://schema.org',
                '@type' => 'Product', 
                'name' => $item->getName(),
                'url' => $this->getItemUrl($item->getSku()),
                'description' => $this->stripTags($item->getDescription()),
                'image' => $this->getBaseUrl() . $item->getImage(),
                'sku' => $item->getSku(),
                'offers' => array(
                    '@type' => 'Offer',
                    'availability' => 'https://schema.org/OnlineOnly',
                    'price' => str_replace(',','',number_format($item->getFinalPrice(),2)),
                    'priceCurrency' => $this->getCurrencyCode(),
                    'url' => $this->getItemUrl($item->getSku())
                )
            );
            array_push($products, $itemp);
        }
        return json_encode($products);
    }

    public function getFitmentParam($segment = array()){
        $fitmentSerial = $this->getRequest()->getParam('ymm_params',null);
        if(is_null($fitmentSerial)){
            return null;
        }
        $fitment = unserialize($fitmentSerial);

        if(is_array($segment)){
            if(empty($segment)){
                return null;
            }
            $fitmentSegments = array();
            foreach($segment as $index){
                if(array_key_exists($index, $fitment)){
                    $fitmentSegments[$index]  = $fitment[$index];
                }
            }
            return $fitmentSegments;
        }else{
            if(!array_key_exists($segment, $fitment)){
                return null;
            }
            return $fitment[$segment];
        }
    }
    public function getWebsite(){
        return $this->getStore()->getWebsite();
    }
    public function getItemUrl($sku){
        return $this->getBaseUrl() . 'sku/' . $sku .'.html';
    }
    public function getBaseUrl(){
        return Mage::getBaseUrl();
    }
}
