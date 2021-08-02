<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/13/17
 * Time: 10:22 PM
 */

class Homebase_Sitemap_Block_Adminhtml_Multimap_Grid_Renderer_Link extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    public function render(Varien_Object $row)
    {
        $fileName = preg_replace('/^\//', '', $row->getPath() . $row->getFilename());
        $url = $this->escapeHtml(
            Mage::app()->getStore($row->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $fileName
        );
        if (file_exists(BP . DS . $fileName)) {
            return sprintf('<a href="%1$s">%1$s</a>', $url);
        }
        return $url;
    }
}