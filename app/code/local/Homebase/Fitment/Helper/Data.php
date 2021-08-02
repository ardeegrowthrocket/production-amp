<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/24/17
 * Time: 4:20 PM
 */

class Homebase_Fitment_Helper_Data extends Mage_Core_Helper_Abstract {
    /** @var Mage_Core_Model_Resource $_resource  */
    protected $_resource;

    /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
    protected $_reader;

    /** @var Magento_Db_Adapter_Pdo_Mysql */
    protected $_writer;

    public function __construct()
    {
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_reader = $this->_resource->getConnection('core_read');
        $this->_writer = $this->_resource->getConnection('core_write');
    }

    /**
     *
     * Return fitments associated with the provided product ID
     *
     * The response array content should look like this
     * array(
     *      array('y'=> '', 'm' => '', 'ml'=> '', 'id' => '')
     *      array('y'=> '', 'm' => '', 'ml'=> '', 'id' => '')
     * )
     * @param $productId
     * @return array
     *
     */
    public function fetchFitmentCollection($productId){
        $fitmentTable = $this->_resource->getTableName('hautopart/combination_list');
        $select = $this->_reader->select()
            ->from(array('f' => $fitmentTable ))
            ->where('f.product_id = ?', $productId);
        $query = $select->query();
        $result = $query->fetchAll();
        $formattedResult = array();
        foreach($result as $item){
            $newItem = array(
                'y' => $item['year'],
                'm' => $item['make'],
                'ml' => $item['model'],
                'id' => $item['id']
            );
            array_push($formattedResult,implode('-',$newItem));
        }
        return $formattedResult;
    }
    public function mergeArrayByColumnValue($array1, $array2, $column = 'id'){
        $newArray = array();
        foreach($array1 as $item){
            $key = array_search($item[$column], array_column($array2,$column));

            if($key !== false){
                $newArray[] = array_merge($item, $array2[$key]);
                unset($array2[$key]);
            }else{
                array_push($newArray,$item);
            }
        }
        $resultArray = array_merge($newArray,$array2);
        return array_filter($resultArray);
    }
}