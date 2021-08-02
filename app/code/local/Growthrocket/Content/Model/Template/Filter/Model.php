<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/9/18
 * Time: 10:04 AM
 */

class Growthrocket_Content_Model_Template_Filter_Model extends Growthrocket_Content_Model_Template_Filter_Part_Model{

    public function __construct(){
        parent::__construct();
        $this->supportedVars[] = 'ymm';
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
        }else if(trim($construction[2]) == $this->supportedVars[3]){
            $replacedValue = $this->getModel($this->fitment['model']);
        }else if(trim($construction[2]) == $this->supportedVars[4]){
            $replacedValue = $this->getYmm($this->fitment);
        }
        else{
            $replacedValue = $construction[0];
        }
        return $replacedValue;
    }

    public function getYmm($params){

        $response = '';
        $helper = Mage::helper("hfitment/url");
        $make = $helper->getOptionText('make', $params['make'], 0, true);
        $model = $helper->getOptionText('model', $params['model'], 0, true);

        $reader = $this->getReader();
        $table = $this->getCoreResource()->getTable('hautopart/combination');

        $select = $reader->select();

        $select->from(array('a' => $table));

        foreach($params as $key => $value){
            $select->where($key . ' = ?', $value);
        }
        $select->group('a.year');
        $result = $select->query();
        $matches = $result->fetchAll(PDO::FETCH_COLUMN,1);
        if(count($matches) > 3){
            $randomIndices = array_rand($matches, 3);
            $selectFew = array();

            foreach($randomIndices as $index){
                array_push($selectFew, $matches[$index]);
            }
            $stringMatches = array();
            foreach($selectFew as $match){
                $year = $helper->getOptionText('year', $match, 0, true);
                array_push($stringMatches, $year . ' '  . $make . ' ' . $model);
            }

            $lastItem = array_pop($stringMatches);
            $response = implode(', ', $stringMatches) . ' and ' . $lastItem;
        }else{
            $stringMatches = array();
            foreach($matches as $match){
                $year = $helper->getOptionText('year', $match, 0, true);
                array_push($stringMatches, $year . ' '  . $make . ' ' . $model);
            }
            $lastItem = array_pop($stringMatches);
            $response = implode(', ', $stringMatches) . ' and ' . $lastItem;
        }
        return $response;
    }
}