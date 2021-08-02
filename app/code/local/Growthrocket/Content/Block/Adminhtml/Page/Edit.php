<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 3:46 PM
 */
class Growthrocket_Content_Block_Adminhtml_Page_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{
    public function __construct(){
        parent::__construct();
        $this->_blockGroup = 'grcontent';
        $this->_controller = 'adminhtml_page';
    }

    public function getHeaderHtml(){
        $model = Mage::registry('grcontent_page');
        if($model->getId()){
            return '<h3>' . Mage::helper('grcontent')->__('Edit Page ') . $model->getUrl() .  '</h3>';
        }else{
            return '<h3>' . Mage::helper('grcontent')->__('New Page') . '</h3>';
        }

    }
}