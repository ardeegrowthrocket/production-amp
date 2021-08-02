<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/13/17
 * Time: 5:01 PM
 */

class Homebase_Sitemap_Model_Multimap_Index{

    /**
     * @var Varien_Db_Adapter_Interface $_connection
     */
    protected $_connection;


    /**
     * Resource instance
     *
     * @var Mage_Core_Model_Resource_Db_Abstract
     */
    protected $_resource;

    public function __construct($args)
    {
        $this->_setConnection($args['connection']);
        $this->_setResource($args['resource']);
        $this->_createTemporaryTable();
    }

    protected function _setConnection(Varien_Db_Adapter_Interface $connection)
    {
        $this->_connection = $connection;
    }

    protected function _getTemporaryTable()
    {
        return $this->_resource->getTable('hsitemap/multimap_index_tmp');
    }


    protected function _setResource(Mage_Core_Model_Resource_Db_Abstract $resource)
    {
        $this->_resource = $resource;
    }

    protected function _createTemporaryTable(){
        $this->_connection->dropTemporaryTable($this->_getTemporaryTable());
        $table = $this->_connection->newTable($this->_getTemporaryTable())
            ->addColumn('sku_paths',Varien_Db_Ddl_Table::TYPE_TEXT,null,array(
                'nullable'  => true
            ),'SKU-YMM Paths')
            ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER,null,array(
                'nullable'  => false
            ));
        $this->_connection->createTemporaryTable($table);
    }

    public function insertToTemporaryTable($data){
        $this->_connection->insert($this->_getTemporaryTable(),$data);
    }

    public function fetchRoutes($storeId){
        $_select = $this->_connection->select()
            ->from($this->_getTemporaryTable());
        return $_select;
    }

}