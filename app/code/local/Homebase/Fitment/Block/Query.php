<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 1/2/18
 * Time: 10:29 PM
 */

class Homebase_Fitment_Block_Query extends Homebase_Autopart_Block_Ymm {
    public function __construct()
    {
        $this->_helper = Mage::helper('hautopart');
        $this->setTemplate('hfitment/query/box.phtml');
    }
}