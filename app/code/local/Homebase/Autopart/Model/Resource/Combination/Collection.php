<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/28/17
 * Time: 11:29 PM
 */

class Homebase_Autopart_Model_Resource_Combination_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('hautopart/combination');
    }
}