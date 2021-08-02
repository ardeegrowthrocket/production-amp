<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 11:58 PM
 */
class Growthrocket_Content_Model_Resource_Content_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct(){
        parent::_construct();
        $this->_init('grcontent/content');
    }
}