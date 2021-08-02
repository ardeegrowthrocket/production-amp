<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/5/18
 * Time: 8:37 PM
 */

class Growthrocket_Content_Model_Template_Filter_Part_Make extends Growthrocket_Content_Model_Template_Filter_Part{

    const PRODUCT_MAKE_ATTRIBUTE_ID = 243;
    protected $supportedVars = array(
        'products',
        'part',
        'make'
    );

    public function __construct(){
        parent::__construct();
    }

    public function varDirective($construction){
        if (count($this->_templateVars)==0) {
            // If template preprocessing
            return $construction[0];
        }
        $replacedValue = '{no value}';

        $this->getProducts($this->fitment[$this->supportedVars[1]]);
        if(trim($construction[2]) == $this->supportedVars[1]){
            $replacedValue = $this->fitment[$this->supportedVars[1]];
        }else if(trim($construction[2]) == $this->supportedVars[0]){
            $replacedValue = $this->getProducts($this->fitment[$this->supportedVars[1]]);
        }else if(trim($construction[2]) == $this->supportedVars[2]){
            $replacedValue = $this->getMake($this->fitment['make']);
        }
        else{
            $replacedValue = $construction[0];
        }


        return $replacedValue;
    }

    protected function getMake($makeId){
        /** @var Homebase_Fitment_Helper_Url $helper */
        $helper = Mage::helper("hfitment/url");
        $make = $helper->getOptionText('make', $makeId, 0, true);
        return $make;
    }
    protected function getProducts($partName){
        $make = $this->fitment[$this->supportedVars[2]];
        $productIds = $this->_fetchProducts($partName);

        $productMakes = $this->getProductMakeMatches($make);

        $matches = array();

        foreach($productIds as $productId){
            if(in_array($productId, $productMakes)){
                array_push($matches, $productId);
            }
        }
        $returnString = '';
        if(count($matches)  > 3){
            $selectFew = array();
            $randomIndices = array_rand($matches,3);
            foreach($randomIndices as $index){
                array_push($selectFew,$matches[$index]);
            }
            $names = $this->getProductNames($selectFew);
            $lastItem = array_pop($names);
            $returnString = implode(', ', $names) . ' and ' . $lastItem;
        }else{
            $names = $this->getProductNames($matches);
            $lastItem = array_pop($names);
            $returnString = implode(', ', $names) . ' and ' . $lastItem;
        }

        return $returnString;
    }
    private function getProductNames($arrayIds){
        $names = array();
        $varcharTable = $this->getCoreResource()->getValueTable('catalog/product','varchar');
        $storeId = $this->getStoreId();

        foreach($arrayIds as $id){
            $name = $this->getStoreValue($id, self::PRODUCT_NAME_ATTRIBUTE_ID, $varcharTable, $storeId);
            if(is_null($name)){
                $name = $this->getStoreValue($id, self::PRODUCT_NAME_ATTRIBUTE_ID, $varcharTable, 0);
            }
            array_push($names, $name);
        }
        return $names;
    }
    public function getProductMakeMatches($makeId){
        $select = $this->getReader()->select();
        $fitmentTable = $this->getCoreResource()->getTable('hautopart/combination_list');
        $select->from(array('m' =>$fitmentTable),array('product_id'))
            ->where('m.make = ?', $makeId)
            ->group('m.product_id');
        $result = $select->query();
        return $result->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}