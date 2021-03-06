<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 11/2/17
 * Time: 7:30 PM
 */

class Homebase_Fitment_Helper_Url extends Mage_Core_Helper_Abstract{

    /** @var Mage_Core_Model_Resource $_resource */
    protected $_resource;

    /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
    protected $_reader;

    protected $attributeMap = array();

    public function __construct()
    {
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_reader = $this->_resource->getConnection('core_read');
        $this->attributeMap = array(
            'year'  => 'auto_year',
            'make'  => 'auto_make',
            'model' => 'auto_model',
            'category'   => 'auto_type',
            'part'      => 'part_name'
        );
    }

    public function buildPath($params,$storeId){
        $url = null;
        if(is_array($params)){
//            $fitment = array_filter($params,array($this,'filterArray'),ARRAY_FILTER_USE_KEY);
            $fitment = $this->extractPermittedKeys($params);
            $path = array();
            foreach($fitment as $key => $value){
                if(is_numeric($value)){
                    $label = $this->getOptionText($key,$value,$storeId);
                    if(!$label){
                        $label = $this->getOptionText($key,$value,0);
                        $path[] = $label;
                    }else{
                        $path[] = $label;
                    }
                }else{
                    $path[] = $this->filterTextToUrl($value);
                }
            }
            if(!empty($path)){
                $url = implode('-', $path);
            } 
        }
        return $url;
    }
    public function buildCategoryYmm($params,$storeId){
        $fitmentArray = null;
        if(is_array($params)){
//            $fitment = array_filter($params,array($this,'filterArray'),ARRAY_FILTER_USE_KEY);
            $fitment = $this->extractPermittedKeys($params);
            $oldKeys = array_keys($fitment);
            $values = array_values($fitment);

            $oldKeyString = str_replace('value','category',implode(',',$oldKeys));
            $newKeys = explode(',',$oldKeyString);

            $fitmentArray = array_combine($newKeys,$values);
        }
        return $this->buildPath($fitmentArray,$storeId);
    }
    public function _query($query){

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->query($query);

    }
    public function getOptionText($label,$optionId, $store_id = 0, $raw = false){
        $url = null;
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $_eav */
        $_eav = Mage::getResourceModel('eav/entity_attribute');
        $code = $_eav->getIdByCode(Mage_Catalog_Model_Product::ENTITY,$this->attributeMap[$label]);
        if($code){
            /** @var Varien_Db_Select $_select */
            $_select = $this->_reader->select();
            $_select->from($this->_resource->getTableName('eav/attribute_option_value'))
                ->where('option_id=?',$optionId)
                ->where('store_id=?', $store_id);
            /** @var Varien_Db_Statement_Pdo_Mysql $_result */
            $_result = $_select->query();
            $url = $_result->fetchColumn(3);
            if(!$raw){
                $url = $this->filterTextToUrl($url);
            }
        }
        return $url;
    }
    public function filterTextToUrl($text){
        $parts = explode(' ', $text);
        //Remove multiple spaces
        $parts = array_filter($parts);
        //Replace ampersands with 'AND'
        //Replace backslash with 'AND'
        //Replace dash with ''
        $conditions = array(
            array(
                'needle'    => '&',
                'replace'   => 'and'
            ),
            array(
                'needle'    => '/',
                'replace'   => 'and'
            ),
            array(
                'needle'    => '-',
                'replace'   => ''
            ),
            array(
                'needle'    => ',',
                'replace'   => ''
            )
        );
        foreach($conditions as $condition){
            foreach($parts as $ndx=>$part) {
                $parts[$ndx] = strtolower(str_replace($condition['needle'], $condition['replace'], $part));
            }
        }
        $parts = array_filter($parts);
        $url = implode('-',$parts);
        return $url;
    }
    public function extractPermittedKeys($array){
        $segment = array();
        $keys = array_keys($array);

        foreach($keys as $key){
            if(in_array($key,array('year','make','model','value','category','part'))){
                $segment[$key] = $array[$key];
            }
        }

        return $segment;
    }
    private function filterArray($var){
        return in_array($var,array('year','make','model','value','category','part'));
    }
    public function validateRoute($path, $routeType, $storeId = 1){
        $suffix = sprintf('store_%d', $storeId);
        $table = $this->_resource->getTableName(array('hfitment/fitment_route',$suffix));
        $result = $this->_reader->select()
            ->from($table)
            ->where('path=?', $path)
            ->where('route=?', $routeType)
            ->query();
        return $result->rowCount() > 0;
    }
    public function getCombinationSerialFromRoutePath($path,$route, $storeId = 1){
        $suffix = sprintf('store_%d', $storeId);
        $table = $this->_resource->getTableName(array('hfitment/fitment_route',$suffix));
        $column = null;
        try{
            $result = $this->_reader->select()
                ->from($table)
                ->where('path = ?', $path)
                ->where('route = ?',$route)
                ->query();
            $column = $result->fetchColumn(3);
        }catch(Exception $exception){

        }
        return $column;
    }
    public function validateSku($entityId, $website){
        $table = $this->_resource->getTableName('catalog/product_website');
        $result = $this->_reader->select()
            ->from($table)
            ->where('product_id = ?', $entityId)
            ->where('website_id = ?', $website)
            ->query();
        return $result->rowCount() > 0;
    }

    public function isTableRouteValid($path, $route, $table){
        $result = $this->_reader->select()
            ->from($table)
            ->where('path = ?', $path)
            ->where('route = ?', $route)
            ->query();

        return count($result->fetchAll()) == 0;
    }
}