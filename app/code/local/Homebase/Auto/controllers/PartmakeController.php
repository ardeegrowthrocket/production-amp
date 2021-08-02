<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/27/17
 * Time: 1:59 AM
 */

class Homebase_Auto_PartmakeController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}