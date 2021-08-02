<?php
/**
 * Created by PhpStorm.
 * User: oliver
 * Date: 3/7/2018
 * Time: 5:38 PM
 */

class Homebase_Fitment_Helper_Fitment_Data extends Homebase_Fitment_Helper_Data{
    public function insertFitment($fitmentCollection, $productid = null, $product = null){
        if(is_null($productid) && is_null($product)){
            return;
        }
        foreach($fitmentCollection as $fitmentSerial){
            $fitment = $this->_combine(explode('-', $fitmentSerial));
            if(array_key_exists('id', $fitment)){
                unset($fitment['id']);
            }
            if(!is_null($productid)){
                $fitment['product_id'] = $productid;
                $fitment['product_sku'] = $product->getSku();
            }else{
                $fitment['product_sku'] = $product->getSku();
            }
            $this->_writer->insert($this->_getTable(),$fitment);
        }
    }
    public function updateFitmentWithProductId($sku, $entity_id){
        $table = $this->_getTable();
        $where = $this->_writer->quoteInto('product_sku = ?  AND ISNULL(product_id)', $sku);
        Mage::log($where, null,'sql.log',true);
        $this->_writer->update($table, array('product_id' => $entity_id), $where);
    }
    public function removeFitment($fitmentCollection, $productid = null){
        if(!is_null($productid)){
            foreach($fitmentCollection as $fitmentSerial){
                $fitment = $this->_combine(explode('-', $fitmentSerial));
                if(array_key_exists('id', $fitment)){
                    $result = $this->_writer->delete($this->_getTable(),'id = ' . $fitment['id']);
                    Mage::log($result, null, 'delete.log',true);
                }

            }
        }
    }
    public function _combine($values){
        $keys = $this->_getKeys();
        $array = array();
        foreach($values as $idx => $value){
            $array[$keys[$idx]] = $value;
        }
        return $array;
    }
    public function _getKeys(){
        return array('year','make','model','id');
    }
    protected function _getTable(){
        return  $this->_resource->getTableName('hautopart/combination_list');
    }

    /**
     * @param $ymmString
     * @param $autotype
     * @param $partname
     */
    public function generateCompleteFitment($ymmserials, $autotype, $partname){
        $ymmfitment = array();
        foreach($ymmserials as $ymmserial){
            $ymm = array_splice($this->_combine(explode('-', $ymmserial)),0,3);
            array_push($ymmfitment, $ymm);
        }
        $categories = count($autotype);

        while($categories > 0){

            $categories--;
        }
        Mage::log($ymmfitment, null, 'save.log',true);
    }
    /**
     *
     * @param $fitmentArray
     */
    public function hasOtherFitmentMatches($fitmentArray){

    }
    /**
     * @param $product Mage_Catalog_Model_Product
     * @return array
     */
    public function getStoreIds($product){
        $defaultStoreIds = array();
        if($product instanceof Mage_Catalog_Model_Product){
            $websites = $product->getWebsiteIds();
            foreach($websites as $website){
                /** @var Mage_Core_Model_Website $_website */
                $_website = Mage::getModel('core/website')->load($website);
                $defaultStoreId = $_website->getDefaultStore()->getStoreId();
                array_push($defaultStoreIds,$defaultStoreId);
            }
        }
        return $defaultStoreIds;
    }

    protected function join($array1, $array2){
        $resultingArray = array();
        $size = (count($array1) > count($array2)) ? count($array1) : count($array2);
        if(count($array1) == count($array2)){
            foreach($array1 as $idx => $array){
                $keys = array_merge(array_keys($array), array_keys($array2[$idx]));
                $values = array_merge(array_values($array), array_values($array2[$idx]));
                array_push($resultingArray,array_combine($keys, $values));
            }
        }else{

        }
        return $resultingArray;
    }

    /**
     *
     * Returns the table name with the store id suffix
     * @param $storeId
     * @return string
     */
    public function getFitmentTable($storeId){
        /** @var Homebase_Fitment_Model_Resource_Index_Route $_resource */
        $_resource = Mage::getResourceModel('hfitment/index_route');
        return $_resource->getMainStoreTable($storeId);
    }
    public function test(){

        $array1 = array(
            array(
                'y' => 1,
                'm' => 2,
                'ml' => 2
            ),
            array(
                'y' => 3,
                'm' => 4,
                'ml' => 4
            ),
        );
        $array2 = array(
            array('cat' => 22),
            array('cat' => 23),
            array('cat' => 33)
        );
        $result = $this->join($array1,$array2);
        return $result;
//        $this->getFitmentTable(1);
    }
}