<?php
class Growthrocket_Slider_Model_Mysql4_Grslider extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("slider/grslider", "id");
    }
}