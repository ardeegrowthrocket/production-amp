<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/12/18
 * Time: 4:57 PM
 */

class Growthrocket_Event_Model_Observer{
    public function processPendingEvent(){
        /** @var Growthrocket_Event_Helper_Data $eventHelper */
        $eventHelper = Mage::helper('grevent');
        /** @var Homebase_Autopart_Model_Fitmentbox $_observer */
        $fitmentbox = Mage::getModel('hautopart/fitmentbox');
        if($eventHelper->hasPendingEvent()){
            $fitmentbox->build();
        }
    }
    public function cleanupEvents(){
        /** @var Growthrocket_Event_Helper_Data $eventHelper */
        $eventHelper = Mage::helper('grevent');
        $eventHelper->removeCompletedEvents();
    }
}