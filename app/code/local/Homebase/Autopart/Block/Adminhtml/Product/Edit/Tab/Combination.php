<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/6/17
 * Time: 9:40 PM
 */

class Homebase_Autopart_Block_Adminhtml_Product_Edit_Tab_Combination extends Mage_Adminhtml_Block_Widget implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabUrl(){
        return $this->getUrl('*/*/combination', array('_current' => true));
    }
    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        // TODO: Implement getTabLabel() method.
        return Mage::helper('hautopart')->__("Vehicle Fitment");
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        // TODO: Implement getTabTitle() method.
        return Mage::helper('hautopart')->__("Vehicle Fitment");
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        // TODO: Implement canShowTab() method.
        return true;
    }
    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        // TODO: Implement isHidden() method.
        return false;
    }

    /**
     * @return string
     */
    public function getTabClass() {
        return 'ajax';
    }
}