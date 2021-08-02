<?php
class Growthrocket_Faq_Model_Mysql4_Grfaq extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("faq/grfaq", "id");
    }
}