<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:17 PM
 */

class Growthrocket_Content_Model_Resource_Page_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct(){
        parent::_construct();
        $this->_init('grcontent/page');
    }
}