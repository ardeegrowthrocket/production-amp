<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/25/17
 * Time: 11:42 AM
 */

class Homebase_Auto_Block_Category extends Homebase_Auto_Block_Parent_Template{

    /** @var Mage_Core_Model_Resource_Resource $_resource */
    protected $_resource;

    private $categoryId;

    /** @var  */
    protected $_collection;

    public function __construct()
    {
        parent::__construct();

        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        foreach($params as $ndx => $value){
            $rawText = $this->_helper->getRawOptionText($ndx,$value);
            $this->addCrumb(array(
                'name'  => $ndx,
                'title' => $rawText,
                'label' => $rawText
            ));
        }
        $this->_resource = Mage::getSingleton('core/resource_resource');
        $this->categoryId = $params['category'];
    }

    protected function _prepareLayout()
    {

        $this->getParts();
        parent::_prepareLayout();
    }


    public function getParts()
    {
        if(!$this->_collection) {
            if(!isset($this->categoryId)){
                throw new Exception('Custom category doesn\'t exists');
            }
            $this->_collection  = $this->getAvailableParts($this->categoryId);
        }

        return $this->_collection;
    }

    public function getTitle()
    {
        return $this->_helper->getRawOptionText('category',$this->getCurrentCategory());
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

        $websiteId = Mage::app()->getStore()->getWebsiteId();


        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_resource->getReadConnection();
        $select = $_reader->select()
            ->from(array('p' => $varcharTable))
            ->join(array('web' => 'catalog_product_website'),'web.product_id=p.entity_id')
            ->join(array('s' => $this->getEntityStatus()),'p.entity_id=s.entity_id',array('status' => 'value'))
            ->where('s.attribute_id=?',96)
            ->where('s.value=?',1)
            ->where('p.attribute_id = ?', $categoryAttributeId)
            ->where('p.value LIKE ?', '%' . $attributeValueId . '%')
            ->where('web.website_id = ?', $websiteId)
            ->group('p.entity_id');
        $result = $select->query();
        $productIds = $result->fetchAll(PDO::FETCH_COLUMN,4);
        $partNames = $_reader->select()
            ->from($varcharTable)
            ->where('attribute_id = ?', $partAttributeId)
            ->where('entity_id IN (?)', $productIds)
            ->group('value')
            ->order(array('value ASC'))
            ->query();
        $partCollection = new Varien_Data_Collection();


        $parts = $partNames->fetchAll(PDO::FETCH_COLUMN,5);
        $subCatArray = array();
        foreach($parts as $part){
            $urlFriendlyText = $_helper->filterTextToUrl($part);
            $partObject = new Varien_Object();
            $partObject->addData(array(
                'label' => $part,
                'link'  => $_helper->generateLink($urlFriendlyText,'part'),
            ));
            if(!empty($part) && trim($part) !== ''){
                $partCollection->addItem($partObject);
            }

            $subCatArray[] = $part;
        }
        if(empty(Mage::registry('sub_category_meta'))){
            Mage::register('sub_category_meta',$subCatArray);
        }

        return $partCollection;
    }

    public function getLimit(){
        $parts = $this->getParts();
        return ceil($parts->count() / 4);
    }

    public function getCurrentCategory(){
        return $this->categoryId;
    }

    protected function getEntityStatus(){
        return $this->_resource->getValueTable('catalog/product','int');
    }
}