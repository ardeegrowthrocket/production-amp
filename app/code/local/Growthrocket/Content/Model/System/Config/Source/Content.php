<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/8/18
 * Time: 8:15 AM
 */

class Growthrocket_Content_Model_System_Config_Source_Content{
    public function toOptionArray(){

        $collection = Mage::getModel('grcontent/content')->getCollection();
        $options = array();
        foreach($collection as $item){
            $temp = array(
                'value' => $item->getId(),
                'label' => $item->getName(),
            );
            array_push($options, $temp);
        }
        return $options;
    }
}