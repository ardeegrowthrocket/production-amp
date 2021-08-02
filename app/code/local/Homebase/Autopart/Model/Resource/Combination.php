<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/28/17
 * Time: 11:27 PM
 */

class Homebase_Autopart_Model_Resource_Combination extends Mage_Core_Model_Mysql4_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hautopart/combination','id');
    }

    public function resetAutoIncrement(){
        /** @var string $table */
        $table = $this->getTable('hautopart/combination');

        /** @var Magento_Db_Adapter_Pdo_Mysql $_adapter */
        $_adapter = $this->_getWriteAdapter();
        $_adapter->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $_adapter->truncateTable($table);
        $_adapter->exec('SET FOREIGN_KEY_CHECKS = 1;');

    }
}