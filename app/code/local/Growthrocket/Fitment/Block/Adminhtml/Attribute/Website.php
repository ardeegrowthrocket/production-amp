<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/05/2018
 * Time: 4:43 PM
 */

class Growthrocket_Fitment_Block_Adminhtml_Attribute_Website extends Mage_Core_Block_Template{
    protected function _construct(){
        parent::_construct();
        $this->setTemplate('grfitment/attribute/website.phtml');
    }

    public function getWebsites(){
        /** @var Mage_Core_Model_Resource_Website_Collection $collection */
        $collection = Mage::getModel('core/website')->getCollection();
        return $collection;
    }

    public function getAvailableAdminOptions(){
        $values = $this->getData('store_admin_available_options');

        if(is_null($values)){
            $valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($this->getAttributeObject()->getId())
                ->setStoreFilter(0, false)
                ->load();

            foreach($valuesCollection as $item){
                $values[$item->getId()] = $item->getValue();
            }
            $this->setData('store_admin_available_options', $values);
        }
        return $values;
    }

    /**
     * @return mixed Mage_Catalog_Model_Resource_Eav_Attribute
     */
    public function getAttributeObject()
    {
        return Mage::registry('entity_attribute');
    }

    public function getAvailableAdminOptionsTree(){
        $options = $this->getAvailableAdminOptions();
        /** @var Growthrocket_Fitment_Model_Resource_Website_Collection $collection */
        $collection = Mage::getModel('grfitment/website')->getCollection();
        $collection->getSelect()->group('value_id');
        $collection->getSelect()->columns('COUNT(value_id) as num');
        $websiteCounter = $this->getWebsites()->count();

        $optionArray = array();

        $usedValues = array();
        foreach($collection as $item){
            if($item->getNum() == $websiteCounter){
                array_push($usedValues, intval($item->getValueId()));
            }
        }

        foreach($options as $idx => $option) {
            if (!in_array($idx, $usedValues)) {
                array_push($optionArray, array(
                    'name' => $option,
                    'id' => $idx,
                    'noChild' => true,
                    'is_draggable' => true,
                    'option_id' => $idx,
                ));
            }
        }
        return $optionArray;
    }
    public function getJsonConfig(){
        $websites = $this->getWebsites();

        $config = array();


        $availableConfig = array(
            'name' => 'Unassigned Attributes',
            'children' => $this->getAvailableAdminOptionsTree(),
            'id' => 0,
            'is_draggable' => false,
        );
        array_push($config, $availableConfig);

        foreach($websites as $website){
            array_push($config, array(
                'name' => $website->getName(),
                'id'   => $website->getWebsiteId(),
                'children' => $this->_getAttributeChildren($website->getWebsiteId()),
                'is_draggable' => false,
            ));
        }
        return json_encode($config);
    }

    protected function _getAttributeChildren($websiteId){
        /** @var Growthrocket_Fitment_Model_Resource_Website_Collection $collection */
        $collection = Mage::getModel('grfitment/website')->getCollection();
        $collection->addFieldToFilter('website_id', $websiteId);
        $adminOptions = $this->getAvailableAdminOptions();
        $children = array();
        foreach($collection as $item){
            $child = array(
                'name' => $adminOptions[$item->getValueId()],
                'id' => $item->getValueId(),
                'noChild' => true,
                'is_draggable' => true,
                'option_id' => $item->getValueId(),
            );
            array_push($children,$child);
        }
        return $children;
    }
}