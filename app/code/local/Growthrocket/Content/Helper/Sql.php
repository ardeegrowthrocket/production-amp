<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 11:09 PM
 */

class Growthrocket_Content_Helper_Sql extends Mage_Core_Helper_Abstract{
    /** @var Mage_Core_Model_Resource $resource */
    private $resource;
    /** @var Mage_Eav_Model_Resource_Entity_Attribute $eavResource  */
    private $eavResource;

    public function __construct()
    {
        $this->resource = Mage::getSingleton('core/resource');
        $this->eavResource = Mage::getResourceModel('eav/entity_attribute');
    }

    protected function _getTable($alias){
        return $this->resource->getTableName($alias);
    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getReader(){
        return $this->resource->getConnection('core_read');
    }
    public function getVarcharOptionText($attributeId, $optionId){
        $catalogProductVarcharTable = $this->_getTable('eav/attribute_option_value');
        $attributeModel = Mage::getModel('eav/entity_attribute');
        $attributeModel->load($attributeId);

//        $attributeCode = $this->eavResource->getIdByCode(Mage_Catalog_Model_Product::ENTITY, $attributeName);

        if($attributeModel && $attributeModel->getId()){
            return $this->getOptionText($optionId, $catalogProductVarcharTable);
        }else{
            return '{Attribute name for product entity not found}';
        }
    }
    public function getOptionText($optionId, $table){
        $select = $this->_getReader()->select()
            ->from($table)
            ->where('option_id=?',$optionId)
            ->where('store_id=?', 0);
        $_result = $select->query();
        $value = $_result->fetchColumn(3);
        return $value;
    }

    public function getPageContent($path, $type, $storeId){

        $table = $this->_getTable('grcontent/page');
        $select = $this->_getReader()->select();

        $select->from(array('m' => $table))
            ->where('m.url = ?', $path)
            ->where('m.type = ?', $type)
            ->where('m.store_id = ?', $storeId);
        $result = $select->query();
        $value = $result->fetchColumn(3);
        return $value;
    }
}