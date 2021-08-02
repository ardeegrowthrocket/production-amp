<?php
class Growthrocket_Cmsblog_Model_Wysiwyg_Config extends Mage_Cms_Model_Wysiwyg_Config
{
    /**
     *
     * @param Varien_Object
     * @return Varien_Object
     */
    public function getConfig($data = array())
    {
        $config = parent::getConfig($data);

        $config->setData('files_browser_window_url', Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg_images/index/'));
        $config->setData('directives_url', Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive'));
        $config->setData('directives_url_quoted', preg_quote($config->getData('directives_url')));
        $config->setData('widget_window_url', Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/widget/index'));

        return $config;
    }

}