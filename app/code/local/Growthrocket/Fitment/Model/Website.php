<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15/05/2018
 * Time: 1:51 PM
 */

class Growthrocket_Fitment_Model_Website extends Mage_Core_Model_Abstract{

    protected $_eventPrefix = 'grfitment_category_website';
    protected function _construct(){
        parent::_construct();
        $this->_init('grfitment/website');
    }
    public function getOptionArray(){
        $options = array();
        /** @var Mage_Core_Model_Resource_Store_Group_Collection $_collection */
        $_collection = Mage::getModel('core/store_group')->getCollection();

        foreach($_collection as $_item){
            $options[$_item->getWebsiteId()] = $_item->getName();
        }
        return $options;
    }
}
