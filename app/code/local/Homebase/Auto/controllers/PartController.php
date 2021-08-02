<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/25/17
 * Time: 10:14 PM
 */

class Homebase_Auto_PartController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}