<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:19 PM
 */
class Growthrocket_Content_Block_Adminhtml_Page extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct(){
        $this->_blockGroup = 'grcontent';
        $this->_controller = 'adminhtml_page';
        $this->_headerText = $this->__('Pages');
        parent::__construct();
    }
}