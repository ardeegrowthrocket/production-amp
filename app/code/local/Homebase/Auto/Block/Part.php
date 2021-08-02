<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/27/17
 * Time: 1:02 AM
 */

class Homebase_Auto_Block_Part extends Homebase_Auto_Block_Parent_Template {

    private $title;

    protected $fitment = array('make','model','year');

    public function __construct(){
        parent::__construct();
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $max = count($params);
        $count = 0;
        $lastLabel = array();
        $namebreadCrumb = array();

        foreach($params as $ndx => $param){
            $label = $param;
            $count++;
            if(in_array($ndx,$this->fitment)){
                $label = $this->_helper->getRawOptionText($ndx,$param);
            }

            if($ndx == 'model') {
                $name = Mage::helper('hautopart/parser')->getLabel($param,'name');
            }else{
                $name = $label;
            }

            $title = $name;
            $lastLabel[$ndx] = $name;
            $urlFriendly = $this->_helper->filterTextToUrl($label);

            //category Label
            if($count == 1) {
                $this->_addCategoryToCrumb();
            }

            $link = $this->getLink($urlFriendly,$ndx);
            if($ndx == 'make') {
                $name = "{$params['part']}";
                $urlFriendly = $this->_helper->filterTextToUrl($name);
                $link = $this->_helper->generateLink($urlFriendly,'part');
            }

            if($ndx == 'model') {
                $name = "{$lastLabel['make']} {$params['part']}";
                $urlFriendly = $this->_helper->filterTextToUrl($name);
                $link = $this->_helper->generateLink($urlFriendly,'part-make');
            }

            // Last label
            if($count == $max) {
                $name = implode(' ',$lastLabel);
            }

            $this->addCrumb(array(
                'name'  => $name,
                'title' => $title,
                'label' => $name,
                'link'  => $count == $max ? '' : $link,
            ));
        }

    }

    protected function getLink($urlPath,$ndx){
        $controller = $ndx;
        $path = $urlPath;
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        if($ndx == 'make'){
            $controller = 'part-make';
            $path = $urlPath . '-' . $this->_helper->filterTextToUrl($params['part']);
        }else if($ndx == 'model'){
            $controller = 'part-model';
            $make = $this->_helper->getOptionText('make',$params['make']);
            $path = $make . '-' . $urlPath . '-' . $this->_helper->filterTextToUrl($params['part']);
        }else{
            $make = $this->_helper->getOptionText('make',$params['make']);
            $model = $this->_helper->getOptionText('model',$params['model']);
            $path = $urlPath . '-' . $make . '-' . $model;
        }
        return $this->_helper->generateLink($path,$controller);
    }

    protected function _addCategoryToCrumb()
    {
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $breadcrumbsArray = array();
        if(isset($params['part'])) {

            $storeId = $this->_getStore()->getId();
            $partName = $this->_helper->filterTextToUrl($params['part']);
            $cacheCode = "breadcrumb_category_{$partName}_{$storeId}";

            if (($data_to_be_cached = Mage::app()->getCache()->load($cacheCode))) {
                $breadcrumbsArray = unserialize($data_to_be_cached);

            } else {

                $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('auto_type')
                    ->addAttributeToFilter('part_name', array('eq' => $params['part']))
                    ->addWebsiteFilter($this->_getStore()->getWebsite())
                    ->setPageSize(1);

                $data = $collection->getFirstItem();
                if ($data) {

                    if(is_array($data->getAttributeText('auto_type'))){
                        $category = $data->getAttributeText('auto_type');
                    }else {
                        $category = explode(",", $data->getAttributeText('auto_type'));
                    }

                    if (isset($category[0])) {
                        $categoryUrlSuffix = Mage::helper('catalog/category')->getCategoryUrlSuffix();
                        $categoryTitle = $category[0];
                        $categoryLink = "category/{$this->_helper->filterTextToUrl($categoryTitle)}{$categoryUrlSuffix}";

                        $breadcrumbsArray = array (
                            'name' => $categoryLink,
                            'title' => $categoryTitle,
                            'label' => $categoryTitle,
                            'link' => Mage::getBaseUrl() . $categoryLink,
                        );
                        Mage::app()->getCache()->save(serialize($breadcrumbsArray), $cacheCode);
                    }
                }
            }
            $this->addCrumb($breadcrumbsArray);
        }

    }

    protected function _getStore()
    {
        return Mage::app()->getStore();
    }
}