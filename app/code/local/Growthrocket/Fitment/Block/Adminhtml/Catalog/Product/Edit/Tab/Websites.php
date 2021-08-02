<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/05/2018
 * Time: 11:13 AM
 */

class Growthrocket_Fitment_Block_Adminhtml_Catalog_Product_Edit_Tab_Websites extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Websites{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('grfitment/product/edit/websites.phtml');
    }


    public function hasPreselectedWebsite(){
        return !is_null($this->getRequest()->getParam('website', null));
    }

    public function matchesPreselect($websiteId){
        $request = $this->getRequest();
        return $websiteId == $request->getParam('website', null);
    }
}
