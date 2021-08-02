<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 12/03/2017
 * Time: 12:31 AM
 */

class Homebase_Autopart_Model_Resource_Label_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('hautopart/label');
    }

}