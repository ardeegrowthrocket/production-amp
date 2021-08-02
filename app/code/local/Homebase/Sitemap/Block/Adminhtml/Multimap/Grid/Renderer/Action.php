<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/13/17
 * Time: 10:24 PM
 */

class Homebase_Sitemap_Block_Adminhtml_Multimap_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action{
    public function render(Varien_Object $row)
    {
        $this->getColumn()->setActions(array(array(
            'url'     => $this->getUrl('*/*/generate', array('id' => $row->getId())),
            'caption' => Mage::helper('hsitemap')->__('Generate'),
        )));
        return parent::render($row);
    }
}