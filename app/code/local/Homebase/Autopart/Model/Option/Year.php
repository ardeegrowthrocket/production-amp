<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/1/17
 * Time: 12:03 AM
 */

class Homebase_Autopart_Model_Option_Year extends Homebase_Autopart_Model_Option_Base{
    public function __construct(){
        parent::__construct('auto_year', 'DESC');
    }
    public function toOptionArray(){
        $options = array(
            array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- Please select --')),
        );

        return array_merge($options,$this->_values);
    }
}