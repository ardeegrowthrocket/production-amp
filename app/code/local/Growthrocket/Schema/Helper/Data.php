<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/12/18
 * Time: 11:01 AM
 */
class Growthrocket_Schema_Helper_Data extends Mage_Core_Helper_Abstract{
    const NOT_LOGGED_IN = 0;
    const TAX_CLASS =2;
    const PRICE_INDEX_TABLE = 'catalog_product_index_price_idx';
    public function getIndexedFinalPrice($product){
        if($product instanceof  Mage_Catalog_Model_Product){
            if($product && $product->getId()){
                $website = $this->getWebsite();
                $select = $this->getReader()->select();
                $price = $product->getFinalPrice();
                try{
                    $select->from(self::PRICE_INDEX_TABLE,array('final_price'))
                        ->where('entity_id = ?', $product->getId())
                        ->where('customer_group_id = ?', self::NOT_LOGGED_IN)
                        ->where('tax_class_id = ?', self::TAX_CLASS)
                        ->where('website_id = ?', $website->getId());

                    $result = $select->query();

                    $price = ($result->fetch(PDO::FETCH_COLUMN,0));
                }catch(Exception $exception){

                }

                return number_format($price,2);
            }
        }
    }
    /**
     * @return Mage_Core_Model_Resource_Resource
     */
    public function getResource(){
        return Mage::getResourceModel('core/resource');
    }
    /**
     * @return Magento_Db_Adapter_Pdo_Mysql
     */
    public  function getReader(){
        return $this->getResource()->getReadConnection();
    }
    public function getWebsite(){
        return Mage::app()->getWebsite();
    }

    public function displayProductSchema($productListing = array())
    {
        $prices = array();
        foreach ($productListing as $product){
            $prices[] = $product['price'];
        }
       if(!empty($productListing)){
           foreach ($productListing as $product){
               $schema = [
                   "@context" => "https://schema.org",
                   "@type" =>  "Product",
                   "name" => $product['name'],
                   "url" => $product['url'],
                   "description" => $product['description'],
                   "image" => $product['image'],
                   "brand" =>  mage::Helper('growthrocket_gtm')->getDefaultBrand(),
                   "offers" => [
                       "@type" => "AggregateOffer",
                       "highPrice" => max($prices),
                        "lowPrice" => min($prices),
                        "priceCurrency" => "$$$", 
                        "offerCount" => $product['count'],
                   ]
           ];

               echo '<script type="application/ld+json">' . json_encode($schema) . '</script>';
               break;
           }
       }
    }
}