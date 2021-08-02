<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/26/17
 * Time: 5:57 PM
 */

class Homebase_Auto_Block_Home_Shopby_Category extends Mage_Core_Block_Template{

    protected $title;

    public function __construct(){
        $this->setTemplate("hauto/home/shopby/category.phtml");
    }

    public function getListing(){

        $_store = Mage::app()->getStore();
        $storeId = 1;

        if($_store && $_store->getStoreId()){
            $storeId = $_store->getWebsiteId();
        }

        /** @var Homebase_Auto_Model_Resource_Index_Category $categoryRoutes */
        $categoryRoutes = Mage::getResourceSingleton('hauto/index_category');

        $attributes = $categoryRoutes->fetchAttributeRoute(Homebase_Auto_Model_Resource_Index_Category::AUTO_TYPE_CODE, $storeId);
        $result = $attributes->fetchAll();

        $hautoHelper = Mage::helper('hautopart');
        $collection = new Varien_Data_Collection();
        foreach($result as $item){
            $compoundedCategories = explode(',', $item['value']);
            if(is_array($compoundedCategories)){
                foreach($compoundedCategories as $singleCategory){
                    if($this->isKeyUniqueInCollection($collection, $singleCategory) && !$hautoHelper->isAutoTypeExcluded($singleCategory)){
                        $itemObject = new Varien_Object();
                        $itemObject->addData(array(
                            'title' => $this->getOptionTitle($singleCategory),
                            'link'  => $this->getLink($singleCategory),
                            'iid' => $singleCategory
                        ));
                        $collection->addItem($itemObject);
                    }
                }
            }

        }
        return $collection;
    }
    public function isKeyUniqueInCollection($collection, $key){
        $unique = true;

        foreach($collection as $item){
            $iid = intval($item->getData('iid'));
            $keyInt = intval($key);
            if($iid == $keyInt){
                $unique = false;
                break;
            }
        }
        return $unique;
    }
    public function setTitle($title){
        $this->title = $title;
    }
    public function getTitle(){
        return $this->title;
    }
    protected function getOptionTitle($value){
        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');
        $rawText = $_helper->getRawOptionText('category',$value);
        return $rawText;
    }
    protected function getLink($value){
        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');
        $urlFriendlyText = $_helper->getOptionText('category',$value);
        return $_helper->generateLink($urlFriendlyText,'category');
    }
}