<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 09/03/2017
 * Time: 8:03 PM
 */

class Homebase_Autopart_Model_Resource_Mix_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('hautopart/mix');
    }
}