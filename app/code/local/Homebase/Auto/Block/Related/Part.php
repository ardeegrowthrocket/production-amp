<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 7/3/17
 * Time: 11:37 PM
 */

class Homebase_Auto_Block_Related_Part extends Mage_Core_Block_Template {

    /** @var Mage_Core_Model_Resource_Resource $_resource */
    protected $_resource;

    /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
    protected $_entityAttributeObject;

    /** @var  Homebase_Auto_Helper_Path */
    protected $_helper;

    private $category;

    public function __construct(){
        $this->_resource = Mage::getSingleton('core/resource_resource');
        $this->_entityAttributeObject = Mage::getResourceModel('eav/entity_attribute');
        $this->_helper = Mage::helper('hauto/path');
    }

    public function fetchCategories(){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->_resource->getReadConnection();

        $result = $_reader->select()
            ->from(array('category' => $this->getEntityVarchar()))
            ->where('category.attribute_id = ?', $this->getAttributeId())
            ->group('value')
            ->query();
        $_collection = new Varien_Data_Collection();
        foreach($result as $item){
            $categoryIds = explode(',',$item['value']);
            if(is_array($categoryIds) && count($categoryIds) > 1){
                foreach($categoryIds as $id){
                    if(!$_collection->getItemById($id)){
                        $multi = new Varien_Object();
                        $multi->setId($id);
                        $multi->setLabel($this->_helper->getRawOptionText('category',$id));
                        $_collection->addItem($multi);
                    }
                }
            }else{
                if(!$_collection->getItemById($item['value'])){
                    $obj = new Varien_Object();
                    $obj->setId($item['value']);
                    $obj->setLabel($this->_helper->getRawOptionText('category',$item['value']));
                    $_collection->addItem($obj);
                }
            }
        }
        return $_collection;
    }

    public function getRandomCategory(){
        $collectionArray = $this->fetchCategories()->toArray(array('id','label'));
        $categories = $collectionArray['items'];
        $index =  array_rand($categories);
        return $categories[$index];
    }


    public function fetchPartNames($categoryId){
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $this->getRequest();

        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');

        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->_resource->getReadConnection();

        // Fetch product Ids.
        $select = $_reader->select()
            ->from(array('category' => $this->getEntityVarchar()))
            ->where('category.attribute_id = ?', $this->getAttributeId())
            ->where('value like ?', '%' . $categoryId . '%')
            ->group(array('entity_id'));


        $ymm_params = unserialize($_request->getParam('ymm_params'));
        $params = array();
        foreach($ymm_params as $key => $value){
            if($key !== 'part'){
                $params[$key] = $value;
            }
        }
        if(!empty($params) && count($params) >0){
            $select->join(array('fitment' => $this->getFitmentTable()),'fitment.product_id=entity_id');
            foreach($params as $key=>$value){
                $select->where($key .' = ?', $value);
            }
        }
        $result = $select->query();

        $productIds = $result->fetchAll(PDO::FETCH_COLUMN,4);


        //Fetch partnames

        $partnames = $_reader->select()
            ->from(array('varchar' => $this->getEntityVarchar()))
            ->where('varchar.attribute_id = ?', $this->getPartNameAttributeId())
            ->where('entity_id IN(?)', $productIds)
            ->group('value')
            ->query();

        $parts = $partnames->fetchAll(PDO::FETCH_COLUMN,5);

        $partCollection = new Varien_Data_Collection();
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

    public function getList(){
        $category = $this->getCategory();
        $collection = $this->fetchPartNames($category['id']);

        //Randomize
        $collectionArray = $collection->toArray(array('label','link'));

        $collectionItems = $collectionArray['items'];

        $indexes = array_rand($collectionItems,10);

        $randomCollection = new Varien_Data_Collection();

        foreach($indexes as $index){
            $varObj = new Varien_Object();
            $varObj->setData($collectionItems[$index]);
            $randomCollection->addItem($varObj);
        }
        return $randomCollection;
    }
    public function getCategory(){
        if(!isset($this->category)){
            if(Mage::getStoreConfig('hauto/settings/enable')){
                $category_set = explode(',', Mage::getStoreConfig('hauto/settings/categories'));
                if(count($category_set) > 0){
                    $chosen = $category_set[array_rand($category_set)];
                    $this->category = array(
                        'id'    => $chosen,
                        'label' => Mage::helper('hauto/path')->getRawOptionText('category',$chosen)
                    );
                }else{
                    throw new Exception('Empty category set');
                }
            }else{
                $this->category = $this->getRandomCategory();
            }
        }
        return $this->category;
    }

    public function getTitle(){
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $this->getRequest();
        $params = unserialize($_request->getParam('ymm_params'));
        $ymm = array();
        foreach($params as $key=>$value){
            if($key !== 'part' && $key !== 'year'){
                $ymm[$key] = Mage::helper('hauto/path')->getRawOptionText($key,$value);
            }
        }
        if(array_key_exists('year',$params)){
            $yearLabel = Mage::helper('hauto/path')->getRawOptionText('year',$params['year']);
            array_unshift($ymm,$yearLabel);
        }
        $currentCategory = $this->getCategory();
        $ymm['category'] = $currentCategory['label'];
        return implode(' ', $ymm);
    }

    protected function getAttributeId(){
        return $this->_entityAttributeObject->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Category::AUTO_TYPE_CODE);
    }

    protected function getPartNameAttributeId(){
        return $this->_entityAttributeObject->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Category::PART_NAME_CODE);
    }
    protected function getEntityVarchar(){
        return $this->_resource->getValueTable('catalog/product','varchar');
    }
    protected function getFitmentTable(){
        return  $this->_resource->getTable('hautopart/combination_list');
    }

}