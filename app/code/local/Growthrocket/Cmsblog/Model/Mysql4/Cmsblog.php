<?php
class Growthrocket_Cmsblog_Model_Mysql4_Cmsblog extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("cmsblog/cmsblog", "id");
    }
}