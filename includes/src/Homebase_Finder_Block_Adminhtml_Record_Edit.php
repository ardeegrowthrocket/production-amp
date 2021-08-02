<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/5/16
 * Time: 5:42 PM
 */

class Homebase_Finder_Block_Adminhtml_Record_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'hfinder';
        $this->_controller = 'adminhtml_record';
        $this->_removeButton('reset');

    }
    public function getHeaderHtml()
    {
        return '<h3>' . Mage::helper('hfinder')->__("New Mapping File") . '</h3>';
    }
}