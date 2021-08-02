<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 07/06/2018
 * Time: 2:15 PM
 */

class Growthrocket_Updater_Block_Adminhtml_Import_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId   = 'id';
        $this->_blockGroup = 'grupdater';
        $this->_controller = 'adminhtml_import';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->_updateButton('save','label', $this->__('Process'));
    }
    public function getHeaderText(){
        return Mage::helper('grupdater')->__('Mass Product Price Import');
    }
}