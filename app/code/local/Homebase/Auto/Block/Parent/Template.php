<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/25/17
 * Time: 11:39 AM
 */

class Homebase_Auto_Block_Parent_Template extends Mage_Core_Block_Template{

    private $_crumbs = array();

    /** @var Homebase_Auto_Helper_Path  */
    protected $_helper;

    public function __construct(){
        parent::__construct();
        $this->_helper = Mage::helper('hauto/path');
    }

    protected function _prepareLayout(){
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if($breadcrumbs){
            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));
            foreach($this->_crumbs as $crumb){
                $breadcrumbs->addCrumb($crumb['name'],array(
                    'label' => $crumb['label'],
                    'title' => $crumb['title'],
                    'link'  => (array_key_exists('link',$crumb) ? $crumb['link'] : '')
                ));
            }
        }

    }

    /**
     * @param array $crumb
     * array('name' => '','label' => '','title' => '','link'  => '')
     *
     */
    protected function addCrumb($crumb = array()){
        if(!is_array($crumb)){
            throw new Exception('Parameter not an array');
        }
        $isOneDimensional = true;
        foreach($crumb as $array){
            if(is_array($array)){
                $this->_crumbs[] = $array;
                $isOneDimensional = false;
            }
        }
        if($isOneDimensional){
            $this->_crumbs[] = $crumb;
        }
    }

    protected function removeCrumb($name){
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
    }
}