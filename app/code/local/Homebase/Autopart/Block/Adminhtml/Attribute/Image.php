<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/6/17
 * Time: 8:51 PM
 */

class Homebase_Autopart_Block_Adminhtml_Attribute_Image extends Mage_Core_Block_Template {
    /** @var Mage_Eav_Model_Entity_Attribute $_attribute */
    protected $_attribute;
    /** @var  Homebase_Autopart_Helper_Uploader */
    protected $_helper;
    protected function _construct(){
        parent::_construct();
        $attributeId = $this->getRequest()->getParam('attribute_id');
        $this->_attribute = Mage::getModel('eav/entity_attribute')->load($attributeId);
        $this->setTemplate('homebase/attribute/image.phtml');
        $this->_helper = Mage::helper('hautopart/uploader');
    }

    public function getOptions(){
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection $_option */
        $_options = Mage::getModel('eav/entity_attribute_option')->getCollection();
        $_options->addFieldToFilter('attribute_id', $this->_attribute->getId());

        /** @var Mage_Core_Model_Resource $resource */
        $_resource = Mage::getSingleton('core/resource');

        $table = $_resource->getTableName('eav/attribute_option_value');

        /** @var Varien_Db_Statement_Pdo_Mysql $statement */
        $query = 'SELECT * FROM ' . $table . ' WHERE option_id = :option AND store_id = :store';

        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $_resource->getConnection('core_read');

        /** @var Mage_Eav_Model_Entity_Attribute_Option $_option */

        $options = array();
        foreach($_options as $_option){
            $result = $reader->fetchRow($query,array(
                'option' => $_option->getId(),
                'store' => 0
            ));
           $options[] = array(
               'id' => $result['option_id'],
               'label'  => $result['value']
           );
        }
        return $options;
    }

    public function hasImage($optionId,$websiteId = 1){
        /** @var Homebase_Autopart_Model_Resource_Image_Collection $_image */
        $_image = Mage::getModel('hautopart/image')->getCollection();

        $_image->addFieldToFilter('option_id', $optionId);
        $_image->addFieldToFilter('website_id', $websiteId);

        return (($_image->count() > 0) ? 1 : 0);
    }

    public function getImage($optionId, $websiteId = 1){
        /** @var Homebase_Autopart_Model_Resource_Image_Collection $imageCollection */
        $imageCollection = Mage::getModel('hautopart/image')->getCollection();

        $imageCollection->addFieldToFilter('option_id', $optionId);
        $imageCollection->addFieldToFilter('website_id', $websiteId);

        $_image = $imageCollection->fetchItem();
//        $_image = Mage::getModel('hautopart/image')->load($optionId,'option_id');
        return $this->_helper->getPath($_image->getImgPath());
    }
    public function getWebsites(){
        $collection = Mage::getModel('core/website')->getCollection();
        return $collection;
    }
}