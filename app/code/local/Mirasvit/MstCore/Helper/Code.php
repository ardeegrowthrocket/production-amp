<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/extension_mcore
 * @version   1.0.24
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */


class Mirasvit_MstCore_Helper_Code extends Mage_Core_Helper_Data { const EE_EDITION = 'EE'; const CE_EDITION = 'CE'; protected static $_edition = false; protected $k; protected $s; protected $l; protected $v; protected $b; protected $d; public function getStatus($sp344c5a = null) { $sp9f9575 = $this->spc22e91(); if (strpos($sp9f9575, '127.') !== false || strpos($sp9f9575, '192.') !== false) { return true; } if ($sp344c5a) { $sp179fd5 = $this->sp2af90d($sp344c5a); $spd64bd7 = $this->sp5c81e6($sp179fd5); if ($spd64bd7) { if (get_class($spd64bd7) !== 'Mirasvit_MstCore_Helper_Code') { return $spd64bd7->getStatus(null); } else { return true; } } else { return 'Wrong extension package!'; } } else { return $this->spaf3755(); } return true; } public function getOurExtensions() { $spbf768e = array(); foreach (Mage::getConfig()->getNode('modules')->children() as $sp657df4 => $sp179fd5) { if ($sp179fd5->active != 'true') { continue; } if (strpos($sp657df4, 'Mirasvit_') === 0) { if ($sp657df4 == 'Mirasvit_MstCore' || $sp657df4 == 'Mirasvit_MCore') { continue; } $spe7edba = explode('_', $sp657df4); if ($spd64bd7 = $this->sp5c81e6($spe7edba[1])) { if (method_exists($spd64bd7, '_sku') && method_exists($spd64bd7, '_version') && method_exists($spd64bd7, '_build') && method_exists($spd64bd7, '_key')) { $spbf768e[] = array('s' => $spd64bd7->_sku(), 'v' => $spd64bd7->_version(), 'r' => $spd64bd7->_build(), 'k' => $spd64bd7->_key()); } } } } return $spbf768e; } private function sp5c81e6($spb6f146) { $sp9f095b = Mage::getBaseDir() . '/app/code/local/Mirasvit/' . $spb6f146 . '/Helper/Code.php'; if (file_exists($sp9f095b)) { $spd64bd7 = Mage::helper(strtolower($spb6f146) . '/code'); return $spd64bd7; } return false; } private function sp2af90d($sp344c5a) { if (is_object($sp344c5a)) { $sp344c5a = get_class($sp344c5a); } $sp344c5a = explode('_', $sp344c5a); if (isset($sp344c5a[1])) { return $sp344c5a[1]; } return false; } private function spaf3755() { $sp704617 = true; $sp41c989 = $this->sp8cfd30(); $sp32ec54 = $this->sp986e4d(); if (!$sp32ec54) { $this->sp083512(); $sp32ec54 = $this->sp986e4d(); } if ($sp32ec54 && isset($sp32ec54['status'])) { if ($sp32ec54['status'] === 'active') { if (abs(time() - $sp32ec54['time']) > 24 * 60 * 60) { $this->sp083512(); } $sp704617 = true; } else { $this->sp083512(); $sp704617 = $sp32ec54['message']; } } return $sp704617; } private function sp986e4d() { $sp5c9cd5 = 'mstcore_' . $this->sp9029cd(); $spc55e87 = Mage::getModel('core/flag'); $spc55e87->load($sp5c9cd5, 'flag_code'); if ($spc55e87->getFlagData()) { $sp32ec54 = @unserialize(@base64_decode($spc55e87->getFlagData())); if (is_array($sp32ec54)) { return $sp32ec54; } } return false; } private function sp05cf3c($sp32ec54) { $sp5c9cd5 = 'mstcore_' . $this->sp9029cd(); $spc55e87 = Mage::getModel('core/flag'); $spc55e87->load($sp5c9cd5, 'flag_code'); $sp32ec54 = base64_encode(serialize($sp32ec54)); $spc55e87->setFlagCode($sp5c9cd5)->setFlagData($sp32ec54); $spc55e87->getResource()->save($spc55e87); return $this; } private function sp083512() { $sp24987d = array(); $sp24987d['v'] = 3; $sp24987d['d'] = $this->sp8cfd30(); $sp24987d['ip'] = $this->spc22e91(); $sp24987d['mv'] = Mage::getVersion(); $sp24987d['me'] = $this->sp0e7efa(); $sp24987d['l'] = $this->sp9029cd(); $sp24987d['k'] = $this->_key(); $sp24987d['uid'] = $this->sp0949e4(); $sp455d1c = @unserialize($this->sp72dcfe('http://mirasvit.com/lc/check/', $sp24987d)); if (isset($sp455d1c['status'])) { $sp455d1c['time'] = time(); $this->sp05cf3c($sp455d1c); } return $this; } private function sp72dcfe($sp1d1525, $sp24987d) { $sp5d74a0 = new Varien_Http_Adapter_Curl(); $sp5d74a0->setConfig(array('timeout' => 10)); $sp5d74a0->write(Zend_Http_Client::POST, $sp1d1525, '1.1', array(), http_build_query($sp24987d, '', '&')); $sp32ec54 = $sp5d74a0->read(); $sp32ec54 = preg_split('/^\\r?$/m', $sp32ec54, 2); $sp32ec54 = trim($sp32ec54[1]); return $sp32ec54; } private function spc22e91() { return Mage::helper('core/http')->getServerAddr(false); } private function sp8cfd30() { return Mage::helper('core/url')->getCurrentUrl(); } private function sp0e7efa() { if (!self::$_edition) { $sp8c672e = BP . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . 'Enterprise' . '_' . 'Enterprise' . '.xml'; $sp465af1 = BP . DS . 'app' . DS . 'code' . DS . 'core' . DS . 'Enterprise' . DS . 'Enterprise' . DS . 'etc' . DS . 'config.xml'; $spd4618f = !file_exists($sp8c672e) || !file_exists($sp465af1); if ($spd4618f) { self::$_edition = self::CE_EDITION; } else { self::$_edition = self::EE_EDITION; } } return self::$_edition; } public function _key() { return $this->k; } public function _sku() { return $this->s; } private function sp9029cd() { return $this->l; } public function _version() { return $this->v; } public function _build() { return $this->b; } private function sp2c4f6b() { return $this->d; } private function sp0949e4() { $spb80fe7 = Mage::getConfig()->getResourceConnectionConfig('core_read'); return md5($spb80fe7->dbname . $spb80fe7->dbhost); } public function onControllerActionPredispatch($sp2ec15d) { } public function onModelSaveBefore($sp2ec15d) { } public function onCoreBlockAbtractToHtmlAfter($sp2ec15d) { $sp1e416d = $sp2ec15d->getBlock(); if (is_object($sp1e416d) && substr(get_class($sp1e416d), 0, 9) == 'Mirasvit_') { $sp704617 = $this->getStatus(get_class($sp1e416d)); if ($sp704617 !== true) { $sp2ec15d->getTransport()->setHtml("<ul class='messages'><li class='error-msg'><ul><li>{$sp704617}</li></ul></li></ul>"); } } } }