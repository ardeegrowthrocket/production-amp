<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/27/17
 * Time: 9:36 PM
 */

class Homebase_Auto_PartmodelController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}