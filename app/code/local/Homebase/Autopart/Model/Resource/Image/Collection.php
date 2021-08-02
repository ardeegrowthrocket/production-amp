<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/11/17
 * Time: 3:28 PM
 */

class Homebase_Autopart_Model_Resource_Image_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('hautopart/image');
    }
}