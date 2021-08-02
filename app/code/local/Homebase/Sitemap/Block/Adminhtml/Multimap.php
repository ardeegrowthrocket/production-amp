<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/10/17
 * Time: 9:18 PM
 */

class Homebase_Sitemap_Block_Adminhtml_Multimap extends Mage_Adminhtml_Block_Widget_Grid_Container {
    public function __construct()
    {
        $this->_blockGroup = 'hsitemap';
        $this->_controller = 'adminhtml_multimap';
        $this->_headerText = $this->__('Multiple Sitemap');
        parent::__construct();
    }
}