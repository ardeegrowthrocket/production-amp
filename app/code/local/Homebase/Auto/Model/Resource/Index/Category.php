<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/25/17
 * Time: 2:13 PM
 */

class Homebase_Auto_Model_Resource_Index_Category extends Mage_Index_Model_Resource_Abstract{
    /** @var Homebase_Auto_Helper_Path $_helper */
    protected $_helper;

    const PART_NAME_CODE = 'part_name';
    const AUTO_TYPE_CODE = 'auto_type';
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hauto/combination_indexer','id');
        $this->_helper = Mage::helper('hauto/path');
    }
    public function build(){
        $categoryRoutes = $this->fetchAttributeRoute(self::AUTO_TYPE_CODE);
        $partnameRoutes = $this->fetchAttributeRoute(self::PART_NAME_CODE);
        foreach($categoryRoutes as $categoryRoute){
            $serial = array(
                'category'  => $categoryRoute['option_id']
            );
            $path = $this->_helper->filterTextToUrl($categoryRoute['value']);
            if(!$this->routePathExists('category',$path)){
                $this->getReadConnection()
                    ->insert($this->getMainTable(), array(
                        'route' => 'category',
                        'path'  => $path,
                        'combination'   => serialize($serial)
                    ));
            }
        }
        foreach($partnameRoutes as $partnameRoute){
            $serial = array(
                'part'  => $partnameRoute['value']
            );
            $path = $this->_helper->filterTextToUrl($partnameRoute['value']);
            if(!$this->routePathExists('part',$path)){
                $this->getReadConnection()
                    ->insert($this->getMainTable(), array(
                        'route' => 'part',
                        'path'  => $path,
                        'combination'   => serialize($serial)
                    ));
            }
        }
    }

    /**
     * @param $attribute
     * @return PDO_Statement|Zend_Db_Statement
     */
    public function fetchAttributeRoute($attribute,$storeId = 0){
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute');

        $attributeId = $entityAttribute->getIdByCode(Mage_Catalog_Model_Product::ENTITY,$attribute);
        $varcharTable = $this->getValueTable('catalog/product','varchar');
        $optionValue = $this->getTable('eav/attribute_option_value');

        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getReadConnection();
        if($attribute == self::AUTO_TYPE_CODE){
            $select = $_reader->select()
                ->from(array('var'=>$varcharTable))
                ->join(array('prod' => 'catalog_product_website'),'var.entity_id=prod.product_id')
                ->where('attribute_id=?',$attributeId)
                ->where('prod.website_id=?', $storeId)
                ->group('var.value');
            $result = $select->query();
        }else{
            $result = $_reader->select()
                ->from($varcharTable)
                ->where('attribute_id=?',$attributeId)
                ->group('value')
                ->query();
        }
        return $result;
    }
    public function reindexAll(){
        $this->build();
    }
    private function serialExists($route, $serial){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getReadConnection();
        /** @var Varien_Db_Statement_Pdo_Mysql $result */
        $result = $_reader->select()
                    ->from($this->getMainTable())
                    ->where('route=?', $route)
                    ->where('combination=?', $serial)
                    ->query();
        return ($result->rowCount() > 0);
    }

    private function routePathExists($route, $path){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getReadConnection();
        /** @var Varien_Db_Statement_Pdo_Mysql $result */
        $result = $_reader->select()
            ->from($this->getMainTable())
            ->where('route=?', $route)
            ->where('path=?', $path)
            ->query();
        return ($result->rowCount() > 0);
    }
}