<?php

class Growthrocket_Cmsblog_Controller_Router extends Mage_Core_Controller_Varien_Router_Standard
{

    CONST REAL_MODULE_NAME = 'Growthrocket_Cmsblog';

    public function match(Zend_Controller_Request_Http $request)
    {
        if(!Mage::helper('cmsblog')->isEnableBlog()){
            return false;
        }

        $parentResult = parent::match($request);
        $front = $this->getFront();
        $pathInfo = $request->getPathInfo();
        $pathArray = explode('/', $pathInfo);
        $modulePath = Mage::helper('cmsblog')->getModulePath();
        if(isset($pathArray[1])) {
            $ext = pathinfo($pathArray[2], PATHINFO_EXTENSION);
            if($modulePath == $pathArray[1] && $ext == 'html') {
                $controllerClassName = $this->_validateControllerClassName(self::REAL_MODULE_NAME, 'cmsblog');
                $controllerInstance = Mage::getControllerInstance($controllerClassName,$request, $front->getResponse());
                $identifier = str_replace('.html','',$pathArray[2]);

                $blogCollection =  Mage::getModel("cmsblog/cmsblog")->loadbyIdenfier($identifier);
                if($blogCollection->getIsActive()) {

                    $request->setControllerName('cmsblog');
                    $request->setActionName('index');
                    $request->setDispatched(true);
                    $request->setParam('blog_id', $blogCollection->getId());
                    $controllerInstance->dispatch('index');
                    return true;
                }else {
                    return false;
                }


            }else {
                return false;
            }
        }

        return false;
    }
}
