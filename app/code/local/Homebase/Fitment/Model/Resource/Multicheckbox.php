<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/26/17
 * Time: 7:02 PM
 */

class Homebase_Fitment_Model_Resource_Multicheckbox extends Mage_Core_Model_Resource_Db_Abstract{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('eav/attribute','attribute_id');
    }

    public function getMulticheckboxAttributeCodes(){
        /** @var Magento_Db_Adapter_Pdo_Mysql $read */
        $read = $this->getReadConnection();
        $select = $read->select()
            ->from($this->getMainTable())
            ->where('frontend_input=?','multicheck');
        $results = $read->fetchAll($select);
        $filtered = array_map(function($el){
            return $el['attribute_code'];
        },$results);
        return $filtered;
    }

}