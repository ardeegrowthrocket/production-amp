<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 4:24 PM
 */

class Growthrocket_Content_Model_Template_Filter_Category extends Growthrocket_Content_Model_Template_Filter{
//    const SUPPORTED_VARIABLES = array(
//        'category',
//        'parts'
//    );
    const CUSTOM_CATEGORY_ATTRIBUTE_ID = 251;
    const PRODUCT_STATUS_ATTRIBUTE_ID = 96;
    /** @var Growthrocket_Content_Helper_Sql $sqlHelper */
    private $sqlHelper;

    private $supportedVars = array(
        'category',
        'parts'
    );

    public function __construct(){
        $this->setVariables($this->supportedVars);
        $this->sqlHelper = Mage::helper('grcontent/sql');
    }
    public function varDirective($construction)
    {
        if (count($this->_templateVars)==0) {
            // If template preprocessing
            return $construction[0];
        }
        if(trim($construction[2]) == $this->supportedVars[0]) {
            $replacedValue = $this->getCategory($construction);
        }
        else if(trim($construction[2]) == $this->supportedVars[1]){
            $replacedValue = $this->getParts($construction);
        }else{
            $replacedValue = '{no value}';
        }
        return $replacedValue;
    }

    private function getParts($construction){
        $activeCategoryId = $this->fitment[$this->supportedVars[0]];

        return $this->getAvailableParts($activeCategoryId);
    }
    private function getCategory($construction){
        $activeCategoryId = $this->fitment[$this->supportedVars[0]];
        return $this->sqlHelper->getVarcharOptionText(self::CUSTOM_CATEGORY_ATTRIBUTE_ID, $activeCategoryId);
    }

    protected function getAvailableParts($categoryId){
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute');

        $categoryAttributeId = $entityAttribute->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Category::AUTO_TYPE_CODE);

        $partAttributeId = $entityAttribute->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Category::PART_NAME_CODE);

        /** @var Mage_Core_Model_Resource_Resource $_resource */
        $_resource = Mage::getResourceModel('core/resource');

        $varcharTable =$_resource->getValueTable('catalog/product','varchar');

        $intTable = $_resource->getValueTable('catalog/product','int');

        $websiteId = Mage::app()->getStore()->getWebsiteId();


        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_resource->getReadConnection();
        $select = $_reader->select()
            ->from(array('p' => $varcharTable))
            ->join(array('web' => 'catalog_product_website'),'web.product_id=p.entity_id')
            ->join(array('s' => $intTable),'p.entity_id=s.entity_id',array('status' => 'value'))
            ->where('s.attribute_id=?',self::PRODUCT_STATUS_ATTRIBUTE_ID)
            ->where('s.value=?',1)
            ->where('p.attribute_id = ?', $categoryAttributeId)
            ->where('p.value LIKE ?', '%' . $categoryId . '%')
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

        $parts = $partNames->fetchAll(PDO::FETCH_COLUMN,5);
        $partString = '';
        if(count($parts) > 3){
            $randKeys = array_rand($parts, 3);

            $randomEntry = array();
            foreach($randKeys as $key){
                array_push($randomEntry, $parts[$key]);
            }
            $lastItem = array_pop($randomEntry);
            $firstSecondItem  = implode(', ', $randomEntry);
            $partString = $firstSecondItem . ' or ' . $lastItem;
        }else{
            $partString = implode(', ', $parts);
            if(count($parts) > 1){
                $lastItem = array_pop($parts);
                return implode(', ', $parts) . ' or ' . $lastItem;
            }
        }
        return $partString;
    }
}