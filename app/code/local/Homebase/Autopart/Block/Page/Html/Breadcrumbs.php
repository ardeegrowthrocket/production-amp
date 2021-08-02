<?php
class Homebase_Autopart_Block_Page_Html_Breadcrumbs extends Mage_Page_Block_Html_Breadcrumbs
{
    public function removeCrumb($crubName){
        unset($this->_crumbs[$crubName]);
    }
    public function getCrumb($crubName){
        return $this->_crumbs[$crubName];
    }
    public function getAllCrumbs(){
        return $this->_crumbs;
    }
}
			