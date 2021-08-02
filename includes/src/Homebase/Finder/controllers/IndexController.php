<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 11/21/16
 * Time: 11:22 AM
 */

require_once(Mage::getModuleDir('controllers','Amasty_Finder').DS.'IndexController.php');
class Homebase_Finder_IndexController extends Amasty_Finder_IndexController{

    public function indexAction()
    {
        $params = Mage::getSingleton('core/session')->getFinderParams();
        $year = $params['finder'][1];
        $maker = $params['finder'][2];
        $model = $params['finder'][3];
        $year = Mage::getModel('amfinder/value')->load($year,'value_id')->getName();
        $maker = Mage::getModel('amfinder/value')->load($maker,'value_id')->getName();
        $model = Mage::getModel('amfinder/value')->load($model,'value_id')->getName();

        Mage::getSingleton('core/session')->setYmm(array(
            'y' => $year,
            'm' => $maker,
            'ml'=> $model
        ));
        $helper = Mage::helper('hfinder');
        $catId = $helper->getCategoryId($year,$maker, $model);
        if(is_null($catId)){
            $this->norouteAction();
        }
        $current_cat = Mage::getModel('catalog/category')->load($catId);
    
        Mage::getSingleton('core/session')->setHFinder_1(array(
            $params['finder'][1],
            $params['finder'][2],
            $params['finder'][3],
            'last'  => $params['finder'][3],
            'current'  => $params['finder'][3],
            'filter_category_id' => 0
        ));
        $this->_redirectUrl($current_cat->getUrl());
        //$this->_redirect('*/*/test');
    }
    public function searchAction()
    {
        $params = $this->getRequest()->getParams();
        Mage::getSingleton('core/session')->setFinderParams($params);
        $id     = $this->getRequest()->getParam('finder_id');
        $finder = Mage::getModel('amfinder/finder')->setId($id);
        
        $dropdowns = $this->getRequest()->getParam('finder');
        if ($dropdowns){
            $finder->saveFilter($dropdowns, $this->getRequest()->getParam('category_id'));
        }

        $backUrl = Mage::helper('core')->urlDecode($this->getRequest()->getParam('back_url'));
        $backUrl = $this->_getModifiedBackUrl($finder, $backUrl);

        if (Mage::getStoreConfig('amfinder/general/clear_other_conditions')){

            $finders = $finder->getCollection()->addFieldToFilter('finder_id', array('neq' => $finder->getId()));
            foreach ($finders as $f) {
                $f->resetFilter();
            }
        }

        if ($this->getRequest()->getParam('reset')){
            $finder->resetFilter();
            Mage::getSingleton('core/session')->unsHFinder_1();
            
            if (Mage::getStoreConfig('amfinder/general/reset_home')){
                $backUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            } else {
                $backUrl = $finder->removeGet($backUrl, 'find');
            }         
        }

         
        
        $this->getResponse()->setRedirect($backUrl);
    }
}