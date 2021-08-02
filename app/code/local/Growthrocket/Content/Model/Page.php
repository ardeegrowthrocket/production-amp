<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:15 PM
 */

class Growthrocket_Content_Model_Page extends Mage_Core_Model_Abstract{
    protected function _construct(){
        parent::_construct();
        $this->_init('grcontent/page');
    }
}