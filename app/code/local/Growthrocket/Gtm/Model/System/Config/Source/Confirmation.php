<?php

class Growthrocket_Gtm_Model_System_Config_Source_Confirmation
{
    public function toOptionArray(){
        return array(
            array('value'=>'CENTER_DIALOG','label'=>'CENTER_DIALOG'),
            array('value'=>'BOTTOM_RIGHT_DIALOG','label'=>'BOTTOM_RIGHT_DIALOG'),
            array('value'=>'BOTTOM_LEFT_DIALOG','label'=>'BOTTOM_LEFT_DIALOG'),
            array('value'=>'TOP_RIGHT_DIALOG','label'=>'TOP_RIGHT_DIALOG'),
            array('value'=>'TOP_LEFT_DIALOG','label'=>'TOP_LEFT_DIALOG'),
            array('value'=>'BOTTOM_TRAY','label'=>'BOTTOM_TRAY')
        );
    }
}