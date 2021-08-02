<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/25/17
 * Time: 11:57 PM
 */

class Homebase_Fitment_Block_Element_Multicheckbox extends Varien_Data_Form_Element_Abstract{

    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('multicheckbox');
    }
    public function getName()
    {
        $name = parent::getName();
        if (strpos($name, '[]') === false) {
            $name.= '[]';
        }
        return $name;
    }
    public function getElementHtml(){
        $values = $this->getValues();
    }
}