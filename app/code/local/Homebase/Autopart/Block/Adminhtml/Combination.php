<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/28/17
 * Time: 10:34 PM
 */

class Homebase_Autopart_Block_Adminhtml_Combination extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct()
    {
        $this->_blockGroup = 'hautopart';
        $this->_controller = 'adminhtml_combination';
        $this->_headerText = $this->__('Part Model Combination');
        $this->_addButtonLabel = Mage::helper('hautopart')->__('Manually Build Combinations');
        parent::__construct();
    }
}