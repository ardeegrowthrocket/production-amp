<?php

class MagicToolbox_Sirv_Model_Mysql4_Cache_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('sirv/cache');
    }

    public function truncate()
    {
        $connection = $this->getConnection();
        if (method_exists($connection, 'truncate')) {
            $connection->truncate($this->getTable('sirv/cache'));
        } else {
            $sql = 'TRUNCATE TABLE ' . $connection->quoteIdentifier($this->getTable('sirv/cache'));
            $connection->raw_query($sql);
        }
    }
}
