<?php
class Growthrocket_Newslettertracker_Model_Mysql4_Capture extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("newslettertracker/capture", "id");
    }
}