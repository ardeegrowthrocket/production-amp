<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/25/17
 * Time: 11:43 PM
 */

class Homebase_Auto_Block_Home_Category extends Mage_Core_Block_Template{

    protected $randomize;

    protected $categories;

    protected $title;

    protected $partLimit;
    public function __construct(){
        parent::__construct();
        $this->randomize = false;
        $this->categories = array();
        $this->partLimit = 5;
        $this->setTemplate('hauto/home/category.phtml');
    }

    public function setCategories($categories){
        $this->categories = explode(',',$categories);
    }

    public function getCategories(){
        return $this->categories;
    }

    public function setTitle($title){
        $this->title = $title;
    }
    public function getTitle(){
        return $this->title;
    }
    public function setChildLimit($limit){
        $this->partLimit = $limit;
    }
    public function getChildLimit(){
        return $this->partLimit;
    }
    public function getListing(){
        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');
        $categories = $this->getCategories();
        $collection = new Varien_Data_Collection();
        $counter = 1;
        if(!empty($categories)){
            foreach($categories as $category){
                $categoryObject = new Varien_Object();
                $urlFriendlyText = $_helper->getOptionText('category',$category);
                $categoryObject->addData(array(
                    'id'    => $counter,
                    'ref_id' => $counter,
                    'label' => $_helper->getRawOptionText('category',$category),
                    'link'  => $_helper->generateLink($urlFriendlyText,'category'),
                    'image' => $this->getCategoryImage($category),
                    'parts' => $this->getAvailableParts($category)
                ));
                $collection->addItem($categoryObject);
                $counter++;
            }
        }
        return $collection;
    }

    protected function getAvailableParts($attributeValueId){
        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute');

        $categoryAttributeId = $entityAttribute->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Category::AUTO_TYPE_CODE);

        $partAttributeId = $entityAttribute->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Category::PART_NAME_CODE);

        /** @var Mage_Core_Model_Resource_Resource $_resource */
        $_resource = Mage::getResourceModel('core/resource');

        $varcharTable =$_resource->getValueTable('catalog/product','varchar');
//
//        $req = $this->getRequest();
//        Zend_Debug::dump($req->getData());


        $_store = Mage::app()->getStore();

        if($_store){
            $websiteId = $_store->getWebsiteId();
        }



        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_resource->getReadConnection();
        
        $select = $_reader->select()
            ->from(array('p' => $varcharTable))
            ->join(array('web' => 'catalog_product_website'),'web.product_id=p.entity_id')
            ->where('p.attribute_id = ?', $categoryAttributeId)
            ->where('p.value LIKE ?', '%' . $attributeValueId . '%')
            ->where('web.website_id = ?', $websiteId)
            ->group('p.entity_id');
        
        $result = $select->query();

//        $result = $_reader->select()
//            ->from($varcharTable)
//            ->where('attribute_id = ?', $categoryAttributeId)
//            ->where('value = ?', $attributeValueId)
//            ->group('entity_id')
//            ->query();

        $productIds = $result->fetchAll(PDO::FETCH_COLUMN,4);
        $partNames = $_reader->select()
            ->from($varcharTable)
            ->join(array('website' => 'catalog_product_website'),
                'website.product_id = entity_id')
            ->where('attribute_id = ?', $partAttributeId)
            ->where('entity_id IN (?)', $productIds)
            ->where('website.website_id = ?', $_store->getWebsiteId())
            ->limit($this->getChildLimit())
            ->group('value')
            ->query();
        $partCollection = new Varien_Data_Collection();

        $parts = $partNames->fetchAll(PDO::FETCH_COLUMN,5);

        foreach($parts as $part){
            $urlFriendlyText = $_helper->filterTextToUrl($part);
            $partObject = new Varien_Object();
            $partObject->addData(array(
                'label' => $part,
                'link'  => $_helper->generateLink($urlFriendlyText,'part'),
            ));
            $partCollection->addItem($partObject);
        }
        return $partCollection;
    }

    protected function getCategoryImage($attributeValueId){
        /** @var Mage_Core_Model_Resource_Resource $_resource */
        $_resource = Mage::getResourceModel('core/resource');
        $_reader = $_resource->getReadConnection();
        $result = $_reader->select()
            ->from($_resource->getTable('hautopart/attribute_images'))
            ->where('option_id =?',$attributeValueId)
            ->limit(1)
            ->query();
        $img =  $result->fetchColumn(2);
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .'hautopart'. $img;
    }

}