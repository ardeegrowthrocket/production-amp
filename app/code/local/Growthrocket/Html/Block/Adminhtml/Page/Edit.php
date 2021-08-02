<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26/09/2018
 * Time: 10:07 PM
 */

class Growthrocket_Html_Block_Adminhtml_Page_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'grhtml';
        $this->_controller = 'adminhtml_page';
        $this->_formScripts[] = "function disableTextbox(id){" .
            'if($(id).readAttribute("disabled") === null){$(id).disable();}else{ $(id).enable();} }';
    }
    public function getHeaderHtml(){
        $model = Mage::registry('grhtml_page');
        if($model->getId()){
            return '<h3>' . Mage::helper('grhtml')->__('Edit Page Request ') . $model->getUrl() .  '</h3>';
        }else{
            return '<h3>' . Mage::helper('grhtml')->__('New Page Title') . '</h3>';
        }

    }
}