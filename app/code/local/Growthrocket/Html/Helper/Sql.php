<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26/09/2018
 * Time: 4:58 PM
 */

class Growthrocket_Html_Helper_Sql extends Mage_Core_Helper_Abstract{
    /** @var  Mage_Core_Model_Resource $resource  */
    private $resource;
    /** @var Magento_Db_Adapter_Pdo_Mysql $reader  */
    private $reader;

    public function __construct(){
        $this->resource = Mage::getSingleton('core/resource');
        $this->reader = $this->resource->getConnection('core_read');
    }
    public function hasPageRecord($params, $table = 'grhtml_pages'){
        $record = $this->getPageRecord($params, $table);
        if(is_bool($record)){
            return $record;
        }
        return count($record) > 0;
    }

    public function getPageRecord($params, $table = 'grhtml_pages'){
        $select = $this->reader->select()
            ->from(array('m' => $table));
        if(is_array($params)){
            foreach($params as $key => $param){
                $select->where($key . ' = ?', $param);
            }
        }
        return $this->reader->fetchAll($select);
    }
}