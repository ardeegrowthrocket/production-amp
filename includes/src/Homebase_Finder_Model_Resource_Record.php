<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/5/16
 * Time: 12:45 AM
 */

class Homebase_Finder_Model_Resource_Record extends Mage_Core_Model_Mysql4_Abstract{
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hfinder/hfinder_base','id');
    }
}