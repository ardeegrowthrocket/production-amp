<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/5/18
 * Time: 1:21 PM
 */

class Growthrocket_Html_Model_Resource_Page extends Mage_Core_Model_Mysql4_Abstract{
    protected function _construct(){
        $this->_init('grhtml/pages','id');
    }
}