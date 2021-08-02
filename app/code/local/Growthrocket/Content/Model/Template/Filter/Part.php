<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 04/10/2018
 * Time: 1:47 PM
 */


class Growthrocket_Content_Model_Template_Filter_Part extends Growthrocket_Content_Model_Template_Filter{

    const PART_NAME_ATTRIBUTE_ID = 249;
    const PRODUCT_NAME_ATTRIBUTE_ID = 71;
    const PRODUCT_STATUS_ATTRIBUTE_ID = 96;

    protected $supportedVars = array(
        'products',
        'part',
    );

    /** @var Growthrocket_Content_Helper_Sql $sqlHelper */
    protected $sqlHelper;

    public function __construct()
    {
        $this->setVariables($this->supportedVars);
        $this->sqlHelper = Mage::helper('grcontent/sql');
    }

    public function varDirective($construction){
        if (count($this->_templateVars)==0) {
            // If template preprocessing
            return $construction[0];
        }
        $replacedValue = '{no value}';

        if(trim($construction[2]) == $this->supportedVars[1]){
            $replacedValue = $this->fitment[$this->supportedVars[1]];
        }else if(trim($construction[2]) == $this->supportedVars[0]){
            $replacedValue = $this->getProducts($this->fitment[$this->supportedVars[1]]);
        }
        else{
            $replacedValue = $construction[0];
        }
        return $replacedValue;
    }
    public function svarDirective($construction){
        if (count($this->_templateVars)==0) {
            // If template preprocessing
            return $construction[0];
        }
        $replacedValue = '';
        if(trim($construction[2]) == $this->supportedVars[1]){
            $replacedValue = $this->fitment[$this->supportedVars[1]] . 's';
        }else{
            $replacedValue = $construction[0];
        }
        return $replacedValue;
    }
    protected function getProducts($partName){
        $varcharTable = $this->getCoreResource()->getValueTable('catalog/product','varchar');
        $productIds = $this->_fetchProducts($partName);
        $storeId = $this->getStoreId();
        $productNames = array();
        foreach ($productIds as $productId) {
            //Fetch Store specific value
            $name = $this->getStoreValue($productId, self::PRODUCT_NAME_ATTRIBUTE_ID, $varcharTable, $storeId);
            if (is_null($name)) {
                $name = $this->getStoreValue($productId, self::PRODUCT_NAME_ATTRIBUTE_ID, $varcharTable, 0);
            }
            array_push($productNames, $name);
        }
        $selectedFew = array();
        if (count($productNames) > 3) {
            $randomIndices = array_rand($productNames);
            foreach ($randomIndices as $index) {
                array_push($selectedFew, $productNames[$index]);
            }
        } else {
            if (count($productNames) > 0) {
                foreach ($productNames as $productName) {
                    array_push($selectedFew, $productName);
                }
            }
        }
        if (count($selectedFew) > 0) {
            $lastItem = array_pop($selectedFew);
            $storeId = $this->getStoreId();
            $productNames = array();
            foreach ($productIds as $productId) {
                //Fetch Store specific value
                $name = $this->getStoreValue($productId, self::PRODUCT_NAME_ATTRIBUTE_ID, $varcharTable, $storeId);
                if (is_null($name)) {
                    $name = $this->getStoreValue($productId, self::PRODUCT_NAME_ATTRIBUTE_ID, $varcharTable, 0);
                }
                array_push($productNames, $name);
            }
            $selectedFew = array();
            if (count($productNames) > 3) {
                $randomIndices = array_rand($productNames, 3);
                foreach ($randomIndices as $index) {
                    array_push($selectedFew, $productNames[$index]);
                }
            } else {
                if (count($productNames) > 0) {
                    foreach ($productNames as $productName) {
                        array_push($selectedFew, $productName);
                    }
                }
            }

            if (count($selectedFew) > 0) {
                $lastItem = array_pop($selectedFew);
                $string = implode(', ', $selectedFew);
                return $string . ' and ' . $lastItem;
            } else {
                return '{no value}';
            }
        }
    }
    protected function getStoreValue($entityId, $attributeId,$table, $storeId){
        $select = $this->getReader()->select();
        $select->from(array('p' => $table),array('value'))
            ->where('p.attribute_id = ?', $attributeId)
            ->where('p.store_id = ?', $storeId)
            ->where('p.entity_id = ?', $entityId);
        $result = $select->query();
        $values = $result->fetchAll(PDO::FETCH_COLUMN,0);
        return array_pop($values);
    }
    protected function getDefaultValue($entityId, $attributeId, $table){
        $select = $this->getReader()->select();
        $select->from(array('p' => $table),array('value'))
            ->where('p.attribute_id = ?', $attributeId)
            ->where('p.store_id = ?', 0)
            ->where('p.entity_id = ?', $entityId);
        $result = $select->query();
        $values = $result->fetchAll(PDO::FETCH_COLUMN,0);
        return array_pop($values);
    }

    protected function _fetchProducts($partname){
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute');

        $websiteId = $this->getWebsiteId();
        $varcharTable = $this->getCoreResource()->getValueTable('catalog/product','varchar');
        $intTable = $this->getCoreResource()->getValueTable('catalog/product','int');

        $select = $this->getReader()->select();
        $select->from(array('p' => $varcharTable))
            ->join(array('web' => 'catalog_product_website'),'web.product_id=p.entity_id')
            ->join(array('s' => $intTable),'p.entity_id=s.entity_id',array('status' => 'value'))
            ->where('s.attribute_id=?',self::PRODUCT_STATUS_ATTRIBUTE_ID)
            ->where('s.value=?',1)
            ->where('p.attribute_id = ?', self::PART_NAME_ATTRIBUTE_ID)
            ->where('p.value LIKE ?', '%' . $partname . '%')
            ->where('web.website_id = ?', $websiteId)
            ->group('p.entity_id');

        $result = $select->query();
        $productIds = $result->fetchAll(PDO::FETCH_COLUMN,4);

        return $productIds;
    }
}