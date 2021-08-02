<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 12:11 AM
 */

class Growthrocket_Content_Block_Adminhtml_Content_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{
    public function __construct(){
        parent::__construct();
        $this->_blockGroup = 'grcontent';
        $this->_controller = 'adminhtml_content';
    }
    public function getHeaderHtml(){
        $model = Mage::registry('grcontent_content');
        if($model->getId()){
            return '<h3>' . Mage::helper('grcontent')->__('Edit Content ') . $model->getName() .  '</h3>';
        }else{
            return '<h3>' . Mage::helper('grcontent')->__('New Content') . '</h3>';
        }

    }
}