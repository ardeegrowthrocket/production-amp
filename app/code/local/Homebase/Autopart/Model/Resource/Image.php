<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/11/17
 * Time: 3:22 PM
 */

class Homebase_Autopart_Model_Resource_Image extends Mage_Core_Model_Mysql4_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hautopart/attribute_images','id');
    }
}