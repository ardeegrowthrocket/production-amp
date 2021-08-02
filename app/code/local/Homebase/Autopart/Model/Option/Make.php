<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/2/17
 * Time: 11:32 PM
 */

class Homebase_Autopart_Model_Option_Make extends Homebase_Autopart_Model_Option_Base{
    public function __construct(){
        parent::__construct('auto_make');
    }
    public function toOptionArray(){
        $options = array(
            array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- Please select --')),
        );

        return array_merge($options,$this->_values);
    }
}