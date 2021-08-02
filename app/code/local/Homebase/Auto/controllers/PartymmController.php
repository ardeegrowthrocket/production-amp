<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/27/17
 * Time: 9:38 PM
 */

class Homebase_Auto_PartymmController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}
