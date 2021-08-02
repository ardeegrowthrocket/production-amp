<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/23/17
 * Time: 1:38 AM
 */

class Homebase_Auto_CategoryController extends Mage_Core_Controller_Front_Action{


    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}