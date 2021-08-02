<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/5/18
 * Time: 1:20 PM
 */

class Growthrocket_Html_Model_Page extends Mage_Core_Model_Abstract{
    protected function _construct(){
        parent::_construct();
        $this->_init('grhtml/page');
    }
}