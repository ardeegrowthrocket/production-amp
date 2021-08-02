<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 17/10/2018
 * Time: 3:07 PM
 */

class Growthrocket_Html_Block_Adminhtml_Input_Renderer_Textcheck extends Varien_Data_Form_Element_Text{
    public function getElementHtml(){
        $html = '<input id="'.$this->getHtmlId().'" name="'.$this->getName();
        $html.= '" value="'.$this->getEscapedValue().'" '.$this->serialize($this->getHtmlAttributes());
        $html.= ($this->__isChecked() ? 'disabled' : '') . '/>'."\n";
        $html.= '<input type="checkbox" onclick=' . $this->__getOnclick().' name="use_default_' . $this->getName() .'" ';
        $html.= $this->__isChecked() ? 'checked' : '';
        $html.= "/><span>Use default ";
        $html.= $this->getName() . "</span>";
        $html.= $this->getAfterElementHtml();
        return $html;
    }
    private function __getOnclick(){
        return 'disableTextbox(\'' . $this->getHtmlId() .'\')';
    }
    private function __isChecked(){
        if(empty($this->getEscapedValue())){
            return true;
        }else{
            return false;
        }
    }
}