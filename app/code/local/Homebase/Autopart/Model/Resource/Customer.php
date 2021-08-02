<?php
class Homebase_Autopart_Model_Resource_Customer extends Mage_Core_Model_Mysql4_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hautopart/customer_combination','id');
    }


}