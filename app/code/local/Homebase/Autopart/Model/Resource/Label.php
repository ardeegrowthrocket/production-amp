<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 12/03/2017
 * Time: 12:30 AM
 */

class Homebase_Autopart_Model_Resource_Label extends Mage_Core_Model_Mysql4_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hautopart/combination_label','label_id');
    }

    public function resetAutoIncrement(){
        $this->_getWriteAdapter()->truncateTable($this->getTable('hautopart/combination_label'));
    }
}