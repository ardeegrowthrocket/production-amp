<?php

class Growthrocket_Gtm_Model_System_Config_Source_Category
{
    public function toOptionArray(){
        return array(
            array('value'=>'newsletter','label'=>'Footer Newsletter'),
            array('value'=>'contact_us','label'=>'Contact Us'),
        );
    }
}