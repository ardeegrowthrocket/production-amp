<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/27/17
 * Time: 1:40 AM
 */

class Homebase_Auto_Model_Resource_Index_Part extends Mage_Index_Model_Resource_Abstract{

    const PART_NAME_CODE = 'part_name';

    /** @var Homebase_Auto_Helper_Path $_helper */
    private $_helper;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hauto/combination_indexer','id');
        $this->_helper = Mage::helper('hauto/path');
    }
    public function build(){
        //Part Make
        $reponse = $this->fetchPartFitmentRoute('make');
        foreach($reponse as $item){
            $part = $this->_helper->filterTextToUrl($item['value']);
            $make = $this->_helper->getOptionText('make',$item['make']);
            $path = $make . '-' . $part;
            $serial = array(
                'part'  => $item['value'],
                'make'  => $item['make']
            );
            if(!$this->routePathExists('partmake',$path)){
                if($item['value']){
                    $this->getReadConnection()
                        ->insert($this->getMainTable(), array(
                            'route' => 'partmake',
                            'path'  => $path,
                            'combination'   => serialize($serial)
                        ));
                }
            }
        }
        //Part Make Model
        $reponse = $this->fetchPartFitmentRoute(array('make','model'));
        foreach($reponse as $item){
            $part = $this->_helper->filterTextToUrl($item['value']);
            $make = $this->_helper->getOptionText('make',$item['make']);
            $model = $this->_helper->getOptionText('model',$item['model']);
            $path = $make . '-' . $model . '-' . $part;
            $serial = array(
                'part'  => $item['value'],
                'make'  => $item['make'],
                'model' => $item['model']
            );
            if(!$this->routePathExists('partmodel',$path)){
                if($item['value']){
                    $this->getReadConnection()
                        ->insert($this->getMainTable(), array(
                            'route' => 'partmodel',
                            'path'  => $path,
                            'combination'   => serialize($serial)
                        ));
                }

            }
        }
        //Part Make Model Year
        $reponse = $this->fetchPartFitmentRoute(array('make','model','year'));
        foreach($reponse as $item){
            $part = $this->_helper->filterTextToUrl($item['value']);
            $make = $this->_helper->getOptionText('make',$item['make']);
            $model = $this->_helper->getOptionText('model',$item['model']);
            $year =  $this->_helper->getOptionText('year',$item['year']);
            $path = $year. '-' . $make . '-' . $model . '-' . $part;
            $serial = array(
                'part'  => $item['value'],
                'make'  => $item['make'],
                'model' => $item['model'],
                'year'  => $item['year']
            );
            if(!$this->routePathExists('partymm',$path)){
                if($item['value']){
                    $this->getReadConnection()
                        ->insert($this->getMainTable(), array(
                            'route' => 'partymm',
                            'path'  => $path,
                            'combination'   => serialize($serial)
                        ));
                }

            }
        }
    }
    protected function fetchPartFitmentRoute($ymm){
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute');
        $attributeId = $entityAttribute->getIdByCode(Mage_Catalog_Model_Product::ENTITY,self::PART_NAME_CODE);
        $varcharTable = $this->getValueTable('catalog/product','varchar');
        $combinationTable = $this->getTable('hautopart/combination_list');
        $group = array(
            'var.value'
        );
        if(is_array($ymm)){
            $group = array_merge($group,$ymm);
        }else{
            $group[] = $ymm;
        }
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getReadConnection();
        $result = $_reader->select()
            ->from(array('var' => $varcharTable))
            ->join(array('combi' => $combinationTable), 'var.entity_id=combi.product_id')
            ->where('attribute_id=?',$attributeId)
            ->group($group)
            ->query();
        return $result;
    }
    private function routePathExists($route, $path){
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getReadConnection();
        /** @var Varien_Db_Statement_Pdo_Mysql $result */
        $result = $_reader->select()
            ->from($this->getMainTable())
            ->where('route=?', $route)
            ->where('path=?', $path)
            ->query();
        return ($result->rowCount() > 0);
    }
    public function reindexAll(){
        $this->build();
    }
}