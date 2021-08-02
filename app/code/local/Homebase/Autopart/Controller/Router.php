<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/22/17
 * Time: 2:38 AM
 */

class Homebase_Autopart_Controller_Router extends Mage_Core_Controller_Varien_Router_Standard{

    private $allowed_paths;

    /** @var Homebase_Autopart_Helper_Path_Validator  $_helper*/
    private $_helper;

    /** @var Homebase_Autopart_Helper_Parser  $_parser*/
    private $_parser;

    CONST REAL_MODULE_NAME = 'Homebase_Autopart';
    CONST MODULE_ALIAS = 'hautopart';

    public function __construct()
    {
        $this->allowed_paths = array(
            'model',
            'year',
            'make',
            'sku',
            'cat',
            'sku-ymm'
        );

        $this->_helper = Mage::helper('hautopart/path_validator');
        $this->_parser = Mage::helper('hautopart/parser');
    }

    public function match(Zend_Controller_Request_Http $request)
    {
        $parentResult = parent::match($request);
        $front = $this->getFront();
        if(!$parentResult){
            $path = trim($request->getPathInfo(), '/');
            if ($path) {
                $p = explode('/', $path);
            } else {
                $p = explode('/', $this->_getDefaultPath());
            }
            if(sizeof($p) > 1){
                if(in_array($p[0],$this->allowed_paths)){
                    //Generic Only
                    $controllerClassName = $this->_validateControllerClassName(self::REAL_MODULE_NAME, 'model');
                    $controllerInstance = Mage::getControllerInstance($controllerClassName,$request, $front->getResponse());
                    $request->setModuleName(self::MODULE_ALIAS);
                    $request->setControllerModule(self::REAL_MODULE_NAME);
                    $request->setRouteName(self::MODULE_ALIAS);
                    $pathcontroller = $p[0];
                    $actionName = 'index';
                    $props = $p[1];
                    $matches = array();
                    preg_match_all('/(?:[A-Z-a-z0-9][A-Z-a-z0-9]+)/',$props, $matches);
                    $matches = $matches[0];
                    array_pop($matches);
                    $props = $matches;
                    $isRouteValid = true;
                    $gParams = array();
                    switch($pathcontroller){
                        case 'model' :
                            $actionName = 'model';
                            $fitment = $this->_parser->extractMakeModel($props,$controllerInstance);

                            if(!empty($fitment)){
                                $gParams = unserialize($fitment);
                            }else{
                                $isRouteValid = false;
                            }
                            break;
                        case 'year' :
                            $actionName = 'ymm';
                            $fitment = $this->_parser->extractMakeModelYear($props, $controllerInstance);
                            if(!empty($fitment)){
                                $gParams = unserialize($fitment);
                            }else{
                                $isRouteValid = false;
                            }
                            break;
                        case 'sku' :
                            $actionName = 'sku';
                            if(count($props) == 1){
                                $isRouteValid = $this->_helper->isSkuExists($props[0], $controllerInstance);
                            }else{
                                $isRouteValid = false;
                            }

                            break;
                        case 'cat' :

                            $excludeSorting = array('position');
                            if(!empty($request->getParam('order'))){
                                if(in_array($request->getParam('order'), $excludeSorting)){
                                    $request->setParam('order', 'name');
                                }
                            }
                            
                            $actionName = 'cat';
                            Mage::helper('hautopart')->customCategoryRedirect();
                            $fitment = $this->_parser->getMMYCValues($props,$controllerInstance);
                            if(!empty($fitment)){
                                $gParams = unserialize($fitment);
                            }else{
                                $isRouteValid = false;
                            }
                            break;
                        case 'sku-ymm':
                            $actionName = 'ymms';
                            array_shift($p);
                            if(count($p) == 2){
                                $ymm = array_shift($p);
                                $sku = array_shift($p);
                                $matches = array();
//                                $sku = substr($skuSegment,0, strpos($skuSegment,'.html'));
                                preg_match_all('/(?:[-a-z-A-Z0-9]+)/',$sku, $matches);
                                $sku = array_pop($matches);
                                $sku = $sku[0];
                                $sku = str_replace('--',' ',$sku);
                                $isRouteValid = $this->_parser->getYmms($ymm,$sku, $controllerInstance);
                                $_product = Mage::getModel('catalog/product')->loadByAttribute('custom_url_key',$sku);

                                if(!empty($_product)) {
                                    $sku = $_product->getSku();
                                }
                                if($isRouteValid  && $this->_isAvailable($_product)){
                                    $gParams = array(
                                        'sku' => $sku
                                    );
                                }else{
                                    $isRouteValid = false;
                                }
                            }else{
                                $isRouteValid = false;
                            }
                            break;
                        default:
                            $fitment = $this->_parser->extractMake($props,$controllerInstance);
                            if(!empty($fitment)){
                                $gParams = unserialize($fitment);
                            }else{
                                $isRouteValid = false;
                            }
                            break;
                    }
                    if($isRouteValid){
                        $request->setControllerName('model');
                        $request->setActionName($actionName);
                        $request->setDispatched(true);
                        $request->setParam('query', serialize($props));
                        $request->setParam('ymm_params', serialize($gParams));
                        $controllerInstance->dispatch($actionName);
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }

        }
    }
    public function validateControllerFileName($fileName)
    {
        if ($fileName && is_readable($fileName) && false===strpos($fileName, '//')) {

            return true;
        }
        return false;
    }

    protected function _isAvailable($_product)
    {
        $available = true;
        if($_product->getStatus() == 2 || $_product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE){
            $available = false;
        }

        return $available;
    }
}