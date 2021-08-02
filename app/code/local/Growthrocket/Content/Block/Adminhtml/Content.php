<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 12:00 AM
 */
class Growthrocket_Content_Block_Adminhtml_Content extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct(){
        $this->_blockGroup = 'grcontent';
        $this->_controller = 'adminhtml_content';
        $this->_headerText = $this->__('Dynamic Contents');
        parent::__construct();
    }
}