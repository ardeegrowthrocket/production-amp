<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/12/18
 * Time: 4:19 PM
 */

class Growthrocket_Event_Model_Resource_Event_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct(){
        $this->_init('grevent/event');
    }
}