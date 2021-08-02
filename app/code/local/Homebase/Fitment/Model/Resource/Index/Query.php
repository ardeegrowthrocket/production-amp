<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 1/4/18
 * Time: 7:29 PM
 */

class Homebase_Fitment_Model_Resource_Index_Query extends Mage_Index_Model_Resource_Abstract{

    /**
     * Store id
     *
     * @var int
     */
    protected $_storeId                  = null;

    protected $_allowTableChanges        = true;

    protected $_columnsSql               = null;


    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hfitment/fitment_query');
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }
    public function getStoreId()
    {
        if (is_null($this->_storeId)) {
            return (int)Mage::app()->getStore()->getId();
        }
        return $this->_storeId;
    }

    public function getMainTable()
    {
        return $this->getMainStoreTable($this->getStoreId());
    }
    public function getMainStoreTable($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        if (is_string($storeId)) {
            $storeId = intval($storeId);
        }
        if ($this->getUseStoreTables() && $storeId) {
            $suffix = sprintf('store_%d', $storeId);
            $table = $this->getTable(array('hfitment/fitment_query', $suffix));
        } else {
            $table = parent::getMainTable();
        }

        return $table;
    }
    public function getUseStoreTables()
    {
        return true;
    }

    public function _createTable($store){
        $tableName = $this->getMainStoreTable($store);

        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $this->_getWriteAdapter();
        $writer->dropTable($tableName);

        $table = $this->_getWriteAdapter()
            ->newTable($tableName)
            ->setComment('Fitment Combination Query');

        if($this->_columnsSql === null){
            $table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true
            ),'Combination Id')
                ->addColumn('year', Varien_Db_Ddl_Table::TYPE_INTEGER,null,array(
                    'nullable' => false
                ))
                ->addColumn('make', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                    'nullable' => false
                ))
                ->addColumn('model', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                    'nullable' => false,
                ));

        }
        $writer->createTable($table);
    }

    protected function _createTables(){
        if($this->_allowTableChanges){
            foreach (Mage::app()->getStores() as $store) {
                $this->_createTable($store->getId());
            }
        }
        return $this;
    }

    public function reindexAll(){
        $this->_createTables();
        $allowTableChanges = $this->_allowTableChanges;
        if ($allowTableChanges) {
            $this->_allowTableChanges = false;
        }
        $this->beginTransaction();

        return $this;
    }



}