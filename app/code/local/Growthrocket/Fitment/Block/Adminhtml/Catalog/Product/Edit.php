<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15/05/2018
 * Time: 3:17 PM
 */

class Growthrocket_Fitment_Block_Adminhtml_Catalog_Product_Edit extends Mage_Adminhtml_Block_Catalog_Product_Edit{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('grfitment/product/edit.phtml');
        $this->setId('product_edit');
    }

    public function getAssignedWebsites(){
        $id = $this->getRequest()->getParam('id', null);
        if(!is_null($id)){
            /** @var Mage_Catalog_Model_Resource_Product_Website $resource */
            $resource = Mage::getResourceModel('catalog/product_website');
            $result = $resource->getWebsites($id);
            if(count($result) == 1){
                $record = array_pop($result);
                return $record;
            }
        }else{
            return $this->getRequest()->getParam('website',null);
        }
    }
    public function getAvailableOptions($websites = null){

        if(is_null($websites)){
            $websites = $this->getAssignedWebsites();
        }
        /** @var Growthrocket_Fitment_Model_Resource_Website_Collection $collection */
        $collection = Mage::getModel('grfitment/website')->getCollection();
        if(!empty($this->getRequest()->getParam('id'))){
           $collection->addFieldToFilter('website_id',array('in' => $websites)); 
        }
        $options = array();

        foreach($collection as $item){
            array_push($options, intval($item->getValueId()));
        }
        return $options;
    }
}