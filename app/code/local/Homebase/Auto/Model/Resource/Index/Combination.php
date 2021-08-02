<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/24/17
 * Time: 10:42 AM
 */

class Homebase_Auto_Model_Resource_Index_Combination extends Mage_Index_Model_Resource_Abstract{

    /** @var  Homebase_Auto_Helper_Path $_pathHelper */
    protected $_pathHelper;
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hauto/combination_indexer','id');
        $this->_pathHelper = Mage::helper('hauto/path');
    }
    public function build(){
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
        $_productCollection = Mage::getModel('catalog/product')->getCollection();
        $_productCollection->joinTable('hautopart/combination_list','product_id=entity_id',array('year','make','model'));
        $_productCollection->addAttributeToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        /** @var Varien_Db_Statement_Pdo_Mysql $_query */
        $_query = $_productCollection->getSelect()->query();
        while($row = $_query->fetch()){
//            Zend_Debug::dump($row);
        }

//        Zend_Debug::dump($_productCollection->getItems());
//        foreach($_productCollection as $_productItem){
//
////            $this->buildSerial(serialize($_productItem->toArray(array('year','make','model'))));
////            $this->buildSerial(serialize($_productItem->toArray(array('make'))),'make');
////            $this->buildSerial(serialize($_productItem->toArray(array('make','model'))),'model');
//        }
    }
    /**
     *
     */
    public function rebuild(){
        echo $this->getMainTable();
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
        $_productCollection = Mage::getModel('catalog/product')->getCollection();
        foreach($_productCollection as $_product){
            /** @var Homebase_Autopart_Model_Resource_Combination_Collection $_fitmentCollection */
            $_fitmentCollection = Mage::getModel('hautopart/mix')->getCollection();
            $_fitmentCollection->addFieldToFilter('product_id',$_product->getId());
            foreach($_fitmentCollection as $_fitment){


                var_dump($_fitment->getData());
    

                exit();
                $this->buildSerial(serialize($_fitment->toArray(array('year','make','model'))));
                $this->buildSerial(serialize($_fitment->toArray(array('make'))),'make');
                $this->buildSerial(serialize($_fitment->toArray(array('make','model'))),'model');
            }
        }
    }
    /**
     *
     */
    public function buildCategory(){
        /** @var Homebase_Autopart_Model_Resource_Combination_Collection $_fitmentCollection */
        $_fitmentCollection = Mage::getModel('hautopart/mix')->getCollection();
        $ctr = 0;
        $_eav = Mage::getResourceModel('eav/entity_attribute');
        $code = $_eav->getIdByCode(Mage_Catalog_Model_Product::ENTITY,'auto_type');
        foreach($_fitmentCollection as $_fitment){
            $reader = $this->getReadConnection();
            $_select = $reader->select()
                ->from($this->getValueTable('catalog/product','varchar'))
                ->where('entity_id=?',$_fitment->getProductId())
                ->where('attribute_id=?', $code);
            /** @var Varien_Db_Statement_Pdo_Mysql $result */
            $result = $_select->query();
            if($result->rowCount()>0){
                $categories = explode(',',$result->fetchColumn(5));
                $combination = $_fitment->toArray(array('year','make','model'));
                foreach($categories as $category){
                    if(trim($category) !== ''){
                        $combination['category'] = $category;
                        $this->buildSerial(serialize($combination),'cat');
                        $this->buildSerial(serialize(array('category' => $category)),'category');
                    }
                }
            }
        }
    }

    /**
     * @param String $serial
     */
    public function buildSerial($serial,$route = 'year'){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_read */
        $_read = $this->getReadConnection();
        /** Varien_Db_Select $select */
        $select = $_read->select()
            ->from($this->getMainTable())
            ->where('combination=?', $serial)
            ->where('route=?', $route);
        /** @var Varien_Db_Statement_Pdo_Mysql $result */
        $result = $select->query();
        if($result->rowCount() == 0){
            $fitment = unserialize($serial);
            $path = array();
            foreach($fitment as $label => $item){
                $path[] = $this->_pathHelper->getOptionText($label,$item);
            }
            $url = implode('-',$path);
            try{
                $this->getReadConnection()->insert($this->getMainTable(),array(
                    'route' => $route,
                    'path'  => $url,
                    'combination'   => $serial,
                ));
            }catch(Exception $ex){

            }
        }
    }
    /**
     * @param Homebase_Autopart_Model_Mix $mix
     * @param string $route
     */
    public function rebuildSerial($mix){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_read */
        $_read = $this->getReadConnection();
        //Check Year Route
        $ymm = $mix->toArray(array('year','make','model'));
        $ymmSerial = serialize($ymm);
        /** @var Homebase_Autopart_Model_Resource_Combination_Collection $ycollection */
        $ycollection = Mage::getModel('hautopart/mix')->getCollection();
        foreach($ymm as $ndx=>$value){
            $ycollection->addFieldToFilter($ndx,$value);
        }
        if($ycollection->count() == 0){
            $_read->delete($this->getMainTable(),'combination=\'' . $ymmSerial.'\'');
        }

        //Check Make Route
        $make = $mix->toArray(array('make'));
        $makeSerial = serialize($make);
        /** @var Homebase_Autopart_Model_Resource_Combination_Collection $ycollection */
        $makeCollection = Mage::getModel('hautopart/mix')->getCollection()
            ->addFieldToFilter('make',$make['make']);
        if($makeCollection->count() == 0){
            $_read->delete($this->getMainTable(),'combination=\'' . $makeSerial.'\'');
        }

        //Check Make Model Route
        $makeModel = $mix->toArray(array('make','model'));
        $makeModelSerial = serialize($makeModel);
        /** @var Homebase_Autopart_Model_Resource_Combination_Collection $ycollection */
        $makeModelCollection = Mage::getModel('hautopart/mix')->getCollection();
        foreach($makeModel as $ndx => $value){
            $makeModelCollection->addFieldToFilter($ndx,$value);
        }
        if($makeModelCollection->count() == 0){
            $_read->delete($this->getMainTable(),'combination=\'' . $makeModelSerial.'\'');
        }
    }
    /**
     * @param string $serial
     */
    public function removeSerial($serial){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_read */
        $_read = $this->getReadConnection();
        $combination = unserialize($serial);
        $targetCategory = $combination['category'];
        $ymm = array();
        foreach($combination as $label=>$value){
            if($label !='category'){
                $ymm[$label]= $value;
            }
        }
        $hasMatch = 0;
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_combination */
        $_combination = Mage::getModel('hautopart/mix')->getCollection();
        foreach($ymm as $label => $value){
            $_combination->addFieldToFilter($label,$value);
        }
        foreach($_combination as $_item){
            $_product = Mage::getModel('catalog/product')->load($_item->getProductId());
            $customCategory= explode(',',$_product->getData('auto_type'));
            foreach($customCategory as $category){
                if($category == $targetCategory){
                    $hasMatch = 1;
                    break;
                }
            }
        }
        if($hasMatch === 0){
            $_read->delete($this->getMainTable(), 'combination=\'' . $serial .'\'');
        }
    }

    public function reindexAll(){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_writer */
        $this->beginTransaction();
        $_writer = $this->_getWriteAdapter();
        $_writer->truncateTable($this->getMainTable());
        $this->commit();

        $this->beginTransaction();
        $this->rebuild();
        $this->buildCategory();
        $this->commit();
    }

    /**
     *
     * @Deprecated
     * @param $path
     * @param string $route
     * @return int|string
     */
    public function fetchFitment($path,$route = 'year'){
        $_read = $this->getReadConnection();
        $select = $_read->select()
            ->from($this->getMainTable())
            ->where('path=?',$path)
            ->where('route=?',$route);

        $result = $select->query();
        if($result->rowCount() == 1){
            return $result->fetchColumn(3);
        }
        return -1;
    }
    public function fetchRoute($fitment,$route = 'year'){
        $_read = $this->getReadConnection();
        $select = $_read->select()
            ->from($this->getMainTable())
            ->where('combination=?',$fitment)
            ->where('route=?',$route);

        $result = $select->query();
        if($result->rowCount() == 1){
            return $result->fetchColumn(2);
        }
        return -1;
    }
}