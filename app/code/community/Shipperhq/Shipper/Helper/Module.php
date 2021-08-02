<?php
/**
 * Created by PhpStorm.
 * User: tbernard
 * Date: 12/7/17
 * Time: 3:08 PM
 */

class Shipperhq_Shipper_Helper_Module extends Shipperhq_Shipper_Helper_Data
{
    const FEATURES_ENABLED_CONFIG = 'carriers/shipper/features_enabled';

    private $feature_set = array(
      //'dimship'       => '', //ignore this for now
        'ltl_freight'   => 'Shipperhq_Freight',
        'validation'    => 'Shipperhq_Validation',
        'storepickup'   => 'Shipperhq_Pickup',
        'dropship'      => 'Shipperhq_Splitrates',
        'residential'   => 'Shipperhq_Freight',
        'shipcal'       => 'Shipperhq_Calendar'
    );

    private $modules = array(
        'ShipperHQ'             => 'Shipperhq_Shipper',
        'Frontend'              => 'Shipperhq_Frontend',
        'Date & Calendar'       => 'Shipperhq_Calendar',
        'Freight Options'       => 'Shipperhq_Freight',
        'In-store Pickup'       => 'Shipperhq_Pickup',
        'Multi-Origin Shipping' => 'Shipperhq_Splitrates',
        'Address Validation'    => 'Shipperhq_Validation'
    );

    const MODULES_MISSING = 'carriers/shipper/modules_missing';

    public function getInstalledModules($forDisplay = false)
    {
        $foundModules = array();
        foreach ($this->modules as $displayModuleName => $moduleName) {
            if ($this->isModuleEnabled($moduleName)) {
                $name = $forDisplay ? $displayModuleName : $moduleName;
                $version = (string)Mage::getConfig()->getNode("modules/{$moduleName}/extension_version");
                $foundModules[$name] = $version;
            }
        }
        return $foundModules;
    }

    /**
     * @return bool
     */
    public function isAutoUpdateAllowed() {
        return (bool)Mage::getStoreConfig('carriers/shipper/disable_unused_modules');
    }

    /**
     * @return array
     */
    public function getEnabledModules() {
        return array_keys(array_filter($this->_getModuleStatuses()));
    }

    /**
     * @return bool
     */
    public function isModuleStatusUpdateRequired() {
        return count($this->getUnusedModules()) || count($this->getMissingModules());
    }

    /**
     * @return bool
     */
    public function updateModuleStatuses() {
        if (!$this->isAutoUpdateAllowed()) {
            return false;
        }

        $success = true;
        foreach ($this->getUnusedModules() as $moduleName) {
            $success &= $this->_disableModule($moduleName);
        }
        foreach ($this->getMissingModules() as $moduleName) {
            $success &= $this->_enableModule($moduleName);
        }

        Mage::helper('shipperhq_shipper')->refreshConfig(); // Reload store config
        Mage::getConfig()->cleanCache(); // Reload global config

        return $success;
    }

    /**
     * @return array
     */
    public function getRequiredModules() {
        $usedFeatures = $this->getEnabledFeatures();
        $usedFeatures = array_flip($usedFeatures);
        $neededModules = array_intersect_key($this->feature_set, $usedFeatures);

        foreach ($neededModules as $module) {
            $neededModules = array_merge($neededModules, $this->_getModuleDependencies($module));
        }
        array_unshift($neededModules, "Shipperhq_Shipper", "Shipperhq_Frontend"); // these are always required

        $neededModules = array_unique(array_values($neededModules));

        return $neededModules;
    }

    /**
     * @return array
     */
    public function getEnabledFeatures() {
        return explode('|', Mage::getStoreConfig(self::FEATURES_ENABLED_CONFIG));
    }

    /**
     * @param $features array
     * return bool
     */
    public function setEnabledFeatures($features) {
        $allFeatures = array_keys($this->feature_set);
        $sanitizedFeatures = array_intersect($allFeatures, $features);
        $featuresStr = implode('|', $sanitizedFeatures);
        /** @var Shipperhq_Shipper_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('shipperhq_shipper');
        $dataHelper->saveConfig(self::FEATURES_ENABLED_CONFIG, $featuresStr);
        $dataHelper->refreshConfig();
    }

    /**
     * @return array
     */
    public function getUnusedModules() {
        $enabled = $this->getEnabledModules();
        $needed = $this->getRequiredModules();
        $unused = array_diff($enabled, $needed);
        return array_values($unused);
    }

    /**
     * @return array
     */
    public function getMissingModules() {
        $enabled = $this->getEnabledModules();
        $needed = $this->getRequiredModules();
        $missing = array_diff($needed, $enabled);
        return array_values($missing);
    }

    /**
     * @return array
     */
    protected function _getModuleStatuses() {
        $modules = array();
        foreach ($this->modules as $moduleName) {
            $modules[$moduleName] = Mage::helper('core/data')->isModuleEnabled($moduleName);
        }
        return $modules;
    }

    /**
     * @param $moduleName string
     * @return bool
     */
    protected function _enableModule($moduleName) {
        return $this->_setModuleStatus($moduleName, true);
    }

    /**
     * @param $moduleName string
     * @return bool
     */
    protected function _disableModule($moduleName) {
        return $this->_setModuleStatus($moduleName, false);
    }

    /**
     * @param $moduleName string
     * @return bool
     */
    protected function _moduleNameValid($moduleName) {
        return in_array($moduleName, $this->modules);
    }

    /**
     * @param $moduleName string
     * @return string
     */
    protected function _getModuleXMLFileName($moduleName) {
        return "app/etc/modules/{$moduleName}.xml";
    }

    /**
     * @param $moduleName string
     * @return bool|string
     */
    protected function _loadModuleXML($moduleName) {
        $content = false;
        $fn = $this->_getModuleXMLFileName($moduleName);
        if (file_exists($fn)) {
            $content = file_get_contents($fn);
        }
        return $content;
    }

    /**
     * @param $moduleName string
     * @param $newContent string
     * @return bool|int
     */
    protected function _rewriteModuleXml($moduleName, $newContent) {
        $fn = $this->_getModuleXMLFileName($moduleName);
        if (file_exists($fn)) {
            return file_put_contents($fn, $newContent);
        }
        return false;
    }

    /**
     * @param $fileContents string
     * @param $newStatus bool
     * @return null|string|string[]
     */
    protected function _changeActiveStatus($fileContents, $newStatus) {
        $output = $fileContents;
        if (is_bool($newStatus)) {
            $newStatus = $newStatus ? "true" : "false";
            $output = preg_replace("/^(\s*\<active\>)(\s*\w+\s*)(\<\/active\>)$/im", "$1$newStatus$3", $output);
        }
        return $output;
    }

    protected function _getModuleDependencies($moduleName) {
        $dependsOn = array();

        $depends = Mage::getConfig()->getNode('modules/' . $moduleName . '/depends');

        if ($depends !== false) {
            foreach ($depends->children() as $k => $v) {
                if (substr(strtolower($k),0,9) === 'shipperhq') {
                    $dependsOn[] = $k;
                }
            }
        }

        return $dependsOn;
    }

    /**
     * DON'T USE THIS FUNCTION DIRECTLY. JUST CALL THE _enableModule and _disableModule calls
     *
     * @param $moduleName string
     * @param $newStatus bool
     * @return bool
     */
    protected function _setModuleStatus($moduleName, $newStatus) {
        $success = false;
        if (is_bool($newStatus) && $this->_moduleNameValid($moduleName)) {
            $content = $this->_loadModuleXML($moduleName);
            $content = $this->_changeActiveStatus($content, $newStatus);
            if ($content) {
                $this->_runModuleEnableHelper($moduleName, $newStatus ? "enable" : "disable", "before");
                $success = $this->_rewriteModuleXml($moduleName, $content);
                if ($success) {
                    $this->_runModuleEnableHelper($moduleName, $newStatus ? "enable" : "disable", "after");
                }
            }
        }
        return (bool)$success;
    }

    /**
     * @param $moduleName
     * @return bool|Mage_Core_Helper_Abstract
     */
    protected function _getModuleEnableHelper($moduleName) {
        //$helperName = strtolower($moduleName) . '/module';
        //$helperExits = class_exists(Mage::getConfig()->getHelperClassName($helperName));
        $helperName = "{$moduleName}_Helper_Module";
        $helperExits = class_exists($helperName, false);

        if ($helperExits) {
            return new $helperName;
        }

        return false;
    }

    /**
     * @param string $moduleName
     * @param string $status "enable" | "disable"
     * @param string $timing "before" | "after"
     * @return bool
     */
    protected function _runModuleEnableHelper($moduleName, $status, $timing) {
        $status = ucfirst(strtolower($status));
        $timing = ucfirst(strtolower($timing));

        if (!in_array($status, array("Enable", "Disable")) || !in_array($timing, array("Before", "After"))) {
            return false;
        }

        if ($helper = $this->_getModuleEnableHelper($moduleName)) {
            $method = "module{$status}{$timing}";
            if (method_exists($helper, $method)) {
                return $helper->{$method}();
            }
        }

        return true;
    }
}