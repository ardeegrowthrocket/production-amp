<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 18/05/2018
 * Time: 4:54 PM
 */

class Growthrocket_Fitment_Model_Resource_Website_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct(){
        $this->_init('grfitment/website');
    }
}