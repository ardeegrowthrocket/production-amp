<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/17/17
 * Time: 9:15 PM
 */

class Homebase_Autopart_Model_System_Attribute{
    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $_productAttributes  */
    protected $_productAttributes;

    public function __construct()
    {
        $this->_productAttributes = Mage::getResourceModel('catalog/product_attribute_collection');
    }

    public function toOptionArray(){

        $options = array();
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $_attribute */
        foreach($this->_productAttributes->getItems() as $_attribute){
            if(trim($_attribute->getStoreLabel()) != ''){
                $options[] = array(
                    'value' => $_attribute->getAttributeId(),
                    'label' => $_attribute->getStoreLabel()
                );
            }
        }
        return $options;
    }
}