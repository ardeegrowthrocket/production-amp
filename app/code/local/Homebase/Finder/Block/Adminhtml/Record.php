<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 12/5/16
 * Time: 4:01 PM
 */

class Homebase_Finder_Block_Adminhtml_Record extends Mage_Adminhtml_Block_Widget_Grid_Container {
    public function _construct()
    {
        parent::_construct();
        $this->_blockGroup = 'hfinder';
        $this->_controller = 'adminhtml_record';
        $this->_headerText = $this->__('GrowthRocket Part Finder 2');
        $this->_addButtonLabel = Mage::helper('hfinder')->__("Add Mapping");
    }
}