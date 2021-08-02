<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/12/17
 * Time: 2:40 PM
 */

class Homebase_Autopart_Model_Option_Category extends Homebase_Autopart_Model_Option_Base{
    public function __construct(){
        parent::__construct('auto_type');
    }
    public function toOptionArray(){
        $options = array(
            array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- Please select --')),
        );

        return array_merge($options,$this->_values);
    }
}