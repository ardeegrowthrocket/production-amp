<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/10/17
 * Time: 9:26 PM
 */
class Homebase_Sitemap_Model_Resource_Multimap_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct(){
        $this->_init('hsitemap/multimap');
    }
}