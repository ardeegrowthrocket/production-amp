<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/12/18
 * Time: 4:15 PM
 */

class Growthrocket_Event_Model_Event extends Mage_Core_Model_Abstract{

    protected function _construct(){
        parent::_construct();
        $this->_init('grevent/event');
    }
}