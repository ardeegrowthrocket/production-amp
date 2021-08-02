<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/8/18
 * Time: 4:05 PM
 */

class Growthrocket_Content_Model_Template_Filter_Part_Model extends Growthrocket_Content_Model_Template_Filter_Part_Make{

    public function __construct(){
        parent::__construct();
        $this->supportedVars[] = 'model';

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
        }
        else{
            $replacedValue = $construction[0];
        }
        return $replacedValue;
    }
    public function getModel($modelId){
        /** @var Homebase_Fitment_Helper_Url $helper */
        $helper = Mage::helper("hfitment/url");
        $model = $helper->getOptionText('model', $modelId, 0, true);
        return $model;
    }

}