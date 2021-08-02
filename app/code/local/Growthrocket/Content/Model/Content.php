<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 11:55 PM
 */

class Growthrocket_Content_Model_Content extends Mage_Core_Model_Abstract{
    protected function _construct(){
        parent::_construct();
        $this->_init('grcontent/content');
    }
}