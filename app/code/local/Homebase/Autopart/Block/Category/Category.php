<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/4/17
 * Time: 11:05 PM
 */

class Homebase_Autopart_Block_Category_Category extends Mage_Core_Block_Template implements Homebase_Autopart_Block_Category_CategoryInterface{

    /** @var  Homebase_Autopart_Helper_Parser $_helper */
    protected $_helper;

    public function _construct(){
        parent::_construct();
        $this->_helper  = Mage::helper('hautopart/parser');
    }

    public function getList()
    {
        // TODO: Implement getList() method.
//        Zend_Debug::dump($this);
    }

    public function getAutoName()
    {
        // TODO: Implement getAutoName() method.
    }
}