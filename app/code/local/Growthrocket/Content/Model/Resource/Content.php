<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 11:56 PM
 */

class Growthrocket_Content_Model_Resource_Content extends Mage_Core_Model_Mysql4_Abstract{
    protected function _construct(){
        $this->_init('grcontent/content','id');
    }
}