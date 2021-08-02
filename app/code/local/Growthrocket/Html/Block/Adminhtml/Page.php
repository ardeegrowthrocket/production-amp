<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26/09/2018
 * Time: 9:46 PM
 */

class Growthrocket_Html_Block_Adminhtml_Page extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct()
    {
        $this->_blockGroup ='grhtml';
        $this->_controller = 'adminhtml_page';
        $this->_headerText = $this->__('Pages with Dynamic Titles');
        parent::__construct();
    }
}