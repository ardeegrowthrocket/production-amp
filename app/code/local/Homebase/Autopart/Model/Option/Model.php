<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/2/17
 * Time: 11:34 PM
 */

class Homebase_Autopart_Model_Option_Model extends Homebase_Autopart_Model_Option_Base{

    public function __construct(){
        parent::__construct('auto_model');
    }
    public function toOptionArray(){
        $options = array(
            array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- Please select --')),
        );
        return array_merge($options,$this->_values);
    }
}