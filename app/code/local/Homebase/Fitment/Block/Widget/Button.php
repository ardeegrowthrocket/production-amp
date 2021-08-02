<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/18/17
 * Time: 8:48 PM
 */

class Homebase_Fitment_Block_Widget_Button extends Mage_Adminhtml_Block_Widget{
    public function __construct()
    {
        parent::__construct();
    }

    public function getType()
    {
        return ($type=$this->getData('type')) ? $type : 'button';
    }

    protected function _toHtml()
    {
        $html = $this->getBeforeHtml().'<button '
            . ($this->getId()?' id="{{id}}"':'')
            . ($this->getElementName()?' name="'.$this->getElementName() . '"':'')
            . ' title="'
            . Mage::helper('core')->quoteEscape($this->getTitle() ? $this->getTitle() : $this->getLabel())
            . '"'
            . ' type="'.$this->getType() . '"'
            . ' class="scalable ' . $this->getClass() . ($this->getDisabled() ? ' disabled' : '') . '"'
            . ' style="'.$this->getStyle() .'"'
            . ($this->getValue()?' value="'.$this->getValue() . '"':'')
            . ($this->getDisabled() ? ' disabled="disabled"' : '')
            . '><span><span><span>' .$this->getLabel().'</span></span></span></button>'.$this->getAfterHtml();

        return $html;
    }
}