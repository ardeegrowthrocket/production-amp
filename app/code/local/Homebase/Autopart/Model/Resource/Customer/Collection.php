<?php

class Homebase_Autopart_Model_Resource_Customer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('hautopart/customer');
    }

}