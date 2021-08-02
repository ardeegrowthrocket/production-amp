<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 18/05/2018
 * Time: 4:52 PM
 */

class Growthrocket_Fitment_Model_Resource_Website extends Mage_Core_Model_Mysql4_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('grfitment/category','id');
    }
}