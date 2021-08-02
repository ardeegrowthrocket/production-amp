<?php

class MagicToolbox_Sirv_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $sirvConfig = array();

    protected $isSirvEnabled = false;

    protected $isSirvImageProcessingEnabled = false;

    public function __construct()
    {
        $storeId = Mage::app()->getStore()->getId();

        if (Mage::app()->getStore()->isAdmin()) {
            $this->setStoreConfigForBackend();
        } else {
            $this->setStoreConfig($storeId);
        }

        $this->isSirvEnabled = (bool)$this->getStoreConfig('sirv/general/enabled');
        $this->isSirvImageProcessingEnabled = (bool)$this->getStoreConfig('sirv/general/sirv_image_processing');
    }

    /**
     * Set config values
     *
     * @param string $storeId
     */
    public function setStoreConfig($storeId = null)
    {
        /** @var Mage_Core_Model_Config_Element $config */
        $config = Mage::getConfig()->getNode('default/sirv');
        $config = $config->asArray();
        foreach ($config as $group => $options) {
            foreach ($options as $option => $value) {
                $path = 'sirv/' . $group . '/' . $option;
                $this->sirvConfig[$path] = Mage::getStoreConfig($path, $storeId);
            }
        }
    }

    /**
     * Set config values for backend
     *
     */
    public function setStoreConfigForBackend()
    {
        /** @var $configDataObject Mage_Adminhtml_Model_Config_Data */
        $configDataObject = Mage::getSingleton('adminhtml/config_data');

        if ($this->isAjax()) {
            $request = Mage::app()->getRequest();
            $section = $request->getParam('section');
            $website = $request->getParam('website');
            $store   = $request->getParam('store');
            $configDataObject
                ->setSection($section)
                ->setWebsite($website)
                ->setStore($store);
        }

        //NOTE: indicate if value was inherited from parent scope
        $inherit = null;
        //NOTE: not inherited values of current scope
        $configData = $configDataObject->load();

        /** @var Mage_Core_Model_Config_Element $config */
        $config = Mage::getConfig()->getNode('default/sirv');
        $config = $config->asArray();
        foreach ($config as $group => $options) {
            foreach ($options as $option => $value) {
                $path = 'sirv/' . $group . '/' . $option;
                $this->sirvConfig[$path] = (string)$configDataObject->getConfigDataValue($path, $inherit, $configData);
            }
        }
    }

    /**
     * Check is request is AJAX
     *
     * @return boolean
     */
    public function isAjax()
    {
        $request = Mage::app()->getRequest();
        if ($request->isXmlHttpRequest()) {
            return true;
        }
        if ($request->getParam('ajax') || $request->getParam('isAjax')) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve config value by path
     *
     * @param string $path
     * @return mixed
     */
    public function getStoreConfig($path)
    {
        return $this->sirvConfig[$path];
    }

    /**
     * Whether 'enabled' option is on
     *
     * @return boolean
     */
    public function isSirvEnabled() {
        return $this->isSirvEnabled;
    }

    /**
     * Whether 'sirv_image_processing' option is on
     *
     * @return boolean
     */
    public function isSirvImageProcessingEnabled() {
        return $this->isSirvImageProcessingEnabled;
    }
}
