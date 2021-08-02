<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/17/17
 * Time: 10:29 PM
 */

class Homebase_Auto_Controller_Auto_Router extends Mage_Core_Controller_Varien_Router_Standard{

    protected $_modules = array();
    protected $_routes = array();

    public function __construct(){

    }
    public function match(Zend_Controller_Request_Http $request)
    {
        // TODO: Implement match() method.
        if (!$this->_beforeModuleMatch()) {
            return false;
        }

        $this->fetchDefault();

        $front = $this->getFront();

        $path = trim($request->getPathInfo(), '/');

        if ($path) {
            $p = explode('/', $path);
        } else {
            $p = explode('/', $this->_getDefaultPath());
        }

        if ($request->getModuleName()) {
            $module = $request->getModuleName();
        } else {
            if (!empty($p[0])) {
                $module = $p[0];
            } else {
                $module = $this->getFront()->getDefault('module');
                $request->setAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS, '');
            }
        }
        if (!$module) {
            if (Mage::app()->getStore()->isAdmin()) {
                $module = 'admin';
            } else {
                return false;
            }
        }
        $modules = $this->getModuleByFrontName($module);

        if ($modules === false) {
            return false;
        }

        if (!$this->_afterModuleMatch()) {
            return false;
        }

        $excludeSorting = array('position');
        if(!empty($request->getParam('order'))){
            if(in_array($request->getParam('order'), $excludeSorting)){
                $request->setParam('order', 'name');
            }
        }

        $found = false;
        foreach ($modules as $realModule) {
            $request->setRouteName($this->getRouteByFrontName($module));

            if ($request->getControllerName()) {
                $controller = $request->getControllerName();
            } else {
                if (!empty($p[0])) {
                    $controller = str_replace('-','',$p[0]);
                } else {
                    $controller = $front->getDefault('controller');
                    $request->setAlias(
                        Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                        ltrim($request->getOriginalPathInfo(), '/')
                    );
                }
            }

            if (empty($action)) {
                if ($request->getActionName()) {
                    $action = $request->getActionName();
                } else {
                    if(count($p) == 1){
                        return false;
                    }
                    if(strpos($p[1],'.html') >-1){
                        $action = $front->getDefault('action');
                    }else{
                        $action = $p[1];
                    }
                }
            }
            $this->_checkShouldBeSecure($request, '/'.$module.'/'.$controller.'/'.$action);

            $controllerClassName = $this->_validateControllerClassName($realModule,$controller);
            if (!$controllerClassName) {
                continue;
            }
            $controllerInstance = Mage::getControllerInstance($controllerClassName, $request, $front->getResponse());

            if (!$this->_validateControllerInstance($controllerInstance)) {
                continue;
            }

            if (!$controllerInstance->hasAction($action)) {
                continue;
            }
            $found = true;
            break;
        }

        if (!$found) {
            if ($this->_noRouteShouldBeApplied()) {
                $controller = 'index';
                $action = 'noroute';

                $controllerClassName = $this->_validateControllerClassName($realModule, $controller);
                if (!$controllerClassName) {
                    return false;
                }

                // instantiate controller class
                $controllerInstance = Mage::getControllerInstance($controllerClassName, $request,
                    $front->getResponse());

                if (!$controllerInstance->hasAction($action)) {
                    return false;
                }
            } else {
                return false;
            }
        }
        $endpath = str_replace('.html','',$p[1]);

        if($controller == 'category'){
            Mage::helper('hautopart')->customCategoryRedirect();
        }

        /** @var Homebase_Auto_Helper_Data $_helper */
        $_helper = Mage::helper('hauto');
        if(!$_helper->validAutoRoute($endpath,$controller,$controllerInstance)){
            return false;
        }
        /** Homebase_Auto_PartController $kontroller */
        $kontroller = $controllerInstance;
        $params = $_helper->fetchRouteParams($endpath,$controller,$controllerInstance);

        //Correct module name
        $request->setModuleName('hauto');
        $request->setControllerName($controller);
        $request->setActionName($action);
        $request->setControllerModule($realModule);
        $request->setParam('ymm_params', $params);
        for ($i = 3, $l = sizeof($p); $i < $l; $i += 2) {
            $request->setParam($p[$i], isset($p[$i+1]) ? urldecode($p[$i+1]) : '');
        }
        // dispatch action
        $request->setDispatched(true);
        $controllerInstance->dispatch($action);
        return true;
    }
    public function collectRoutes($configArea, $useRouterName){
        $routers = array();
        $routersConfigNode = Mage::getConfig()->getNode($configArea.'/routers');

        if($routersConfigNode) {
            $routers = $routersConfigNode->children();
        }
        foreach ($routers as $routerName=>$routerConfig) {
            $use = (string)$routerConfig->use;
            if ($use == $useRouterName) {
                $modules = array((string)$routerConfig->args->module);
                if ($routerConfig->args->modules) {
                    foreach ($routerConfig->args->modules->children() as $customModule) {
                        if ((string)$customModule) {
                            if ($before = $customModule->getAttribute('before')) {
                                $position = array_search($before, $modules);
                                if ($position === false) {
                                    $position = 0;
                                }
                                array_splice($modules, $position, 0, (string)$customModule);
                            } elseif ($after = $customModule->getAttribute('after')) {
                                $position = array_search($after, $modules);
                                if ($position === false) {
                                    $position = count($modules);
                                }
                                array_splice($modules, $position+1, 0, (string)$customModule);
                            } else {
                                $modules[] = (string)$customModule;
                            }
                        }
                    }
                }
                $frontName = (string)$routerConfig->args->frontName;
                $this->addModule($frontName, $modules, $routerName);
            }
        }
    }
}