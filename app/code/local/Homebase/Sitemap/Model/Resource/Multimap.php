<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/10/17
 * Time: 9:24 PM
 */

class Homebase_Sitemap_Model_Resource_Multimap extends Mage_Core_Model_Mysql4_Abstract{
    protected function _construct(){
        $this->_init('hsitemap/multimap','id');
    }
}