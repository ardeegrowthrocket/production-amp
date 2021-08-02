<?php
class Magecomp_S3Amazon_Model_Mysql4_Title_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init("s3amazon/title");
    }

}