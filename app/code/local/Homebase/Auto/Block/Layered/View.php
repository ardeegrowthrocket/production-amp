<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/28/17
 * Time: 7:47 PM
 */

class Homebase_Auto_Block_Layered_View extends Mage_Core_Block_Template{

    /** @var Mage_Core_Model_Resource_Resource $_resource */
    protected $_resource;

    /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
    protected $_entityAttributeObject;

    /** @var  Homebase_Auto_Helper_Path */
    protected $_helper;

    protected $_makeCollection;

    public function __construct(){
        $this->_resource = Mage::getSingleton('core/resource_resource');
        $this->_entityAttributeObject = Mage::getResourceModel('eav/entity_attribute');
        $this->_helper = Mage::helper('hauto/path');
    }

    protected function _prepareLayout(){

        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $this->getList();
    }

    protected function getAttributeId(){
        return $this->_entityAttributeObject->getIdByCode(Mage_Catalog_Model_Product::ENTITY,Homebase_Auto_Model_Resource_Index_Part::PART_NAME_CODE);
    }
    
    protected function getEntityVarchar(){
        return $this->_resource->getValueTable('catalog/product','varchar');
    }
    protected function getEntityStatus(){
        return $this->_resource->getValueTable('catalog/product','int');
    }
    protected function getCombinationTable(){
        return $this->_resource->getTable('hautopart/combination_list');
    }

    public function getList(){

        if(!$this->_makeCollection) {
            $params = unserialize($this->getRequest()->getParam('ymm_params'));
            $_store = Mage::app()->getStore();
            /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
            $_reader = $this->_resource->getReadConnection();
            /** @var Varien_Db_Statement_Pdo_Mysql $result */
            $select = $_reader->select()
                ->from(array('var' => $this->getEntityVarchar()))
                ->join(array('combi' => $this->getCombinationTable()), 'var.entity_id=combi.product_id')
                ->join(array('w' => 'catalog_product_website'), 'w.product_id = var.entity_id')
                ->join(array('s' => $this->getEntityStatus()), 'var.entity_id=s.entity_id', array('statusid' => 'attribute_id', 'statusval' => 'value'))
                ->joinLeft(array('label' => 'auto_combination_list_labels'), 'label.option=combi.make', array('label_make' => 'label.label'))
                ->where('w.website_id =?', $_store->getWebsiteId())
                ->where('var.attribute_id=?', $this->getAttributeId())
                ->where('s.attribute_id=?', 96)
                ->where('s.value=?', 1)
                ->where('var.value = ? ', $params['part'])
                ->order('label_make ASC')
                ->group(array('combi.make'));

            if (Mage::getStoreConfig('fitment/configuration/enable', $_store)) {
                $allowedMakes = explode(',', Mage::getStoreConfig('fitment/configuration/make', $storeId));
                $select->where('combi.make IN (?)', $allowedMakes);
            }
            $result = $select->query();

            $filterCollection = new Varien_Data_Collection();
            $makeLabel = array();
            foreach ($result as $item) {
                $itemObject = new Varien_Object();
                $label = $this->_helper->getRawOptionText('make', $item['make']);
                $urlFriendly = $this->_helper->filterTextToUrl($label) . '-' . $this->_helper->filterTextToUrl($params['part']);
                $itemObject->setData(array(
                    'id' => $item['make'],
                    'label' => $label,
                    'link' => $this->_helper->generateLink($urlFriendly, 'part-make')
                ));
                $filterCollection->addItem($itemObject);
                $makeLabel[] = $label;
            }
            Mage::register('data_make_label', $makeLabel, true);
            $this->_makeCollection = $filterCollection;
        }
        return $this->_makeCollection;
    }

    public function getLayerTitle(){
        return 'Make';
    }
}