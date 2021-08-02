<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:16 PM
 */

class Growthrocket_Content_Model_Resource_Page extends Mage_Core_Model_Mysql4_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('grcontent/page','id');
    }
}