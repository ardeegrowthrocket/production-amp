<?php

class Homebase_Autopart_Block_Category_Listing extends Mage_Core_Block_Template
{

    protected $_collection;

    protected $_ymmParams;

    protected $_makeLabel = "";

    protected $_modelLabel = "";

    protected $_yearLabel = "";

    protected $_autoPartsData = array();


    protected function _construct()
    {
        $this->_ymmParams = unserialize($this->getRequest()->getParam('ymm_params'));
        parent::_construct();
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {

        $this->_makeLabel = Mage::Helper('hauto')->getAutoLabelById($this->_ymmParams['make']);

        if(!empty($this->_ymmParams['model'])){
            $this->_modelLabel = Mage::Helper('hauto')->getAutoLabelById($this->_ymmParams['model']);
        }

        if(!empty($this->_ymmParams['year'])){
            $this->_yearLabel = Mage::Helper('hauto')->getAutoLabelById($this->_ymmParams['year']);
        }

        $this->collection();

        return parent::_prepareLayout();
    }

    /**
     * @return Mage_Core_Model_Store
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _getResources()
    {
        return  Mage::getSingleton('core/resource');
    }

    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function collection()
    {

        if(!$this->_collection) {

            $request = Mage::app()->getRequest();
            $route = $request->getRouteName();
            $pathInfo = explode('/', trim($request->getPathInfo(), '/'));

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('auto_type','part_name','image'))
                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addWebsiteFilter($this->_getStore()->getWebsite());

            if(Mage::helper('hautopart')->getSortPartName() == 'bestseller') {
                $collection->getSelect()
                    ->joinLeft(
                        array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                        "e.entity_id = aggregation.product_id", array('SUM(aggregation.qty_ordered) AS sold_quantity')
                    )->order('sold_quantity DESC')->group('e.entity_id');
            }

            if($route == 'hautopart'){

                switch ($pathInfo[0]){
                    case 'year':

                        $collection->getSelect()->joinLeft(
                            array("auto_year" => $this->_getResources()->getTableName('hautopart/combination_list')),
                            "e.entity_id = auto_year.product_id and auto_year.make={$this->_ymmParams['make']} and auto_year.model={$this->_ymmParams['model']}" ,array('auto_year' => 'auto_year.year'));
                        $collection->getSelect()->where("auto_year.year={$this->_ymmParams['year']}");

                        break;
                    case 'make':

                        $collection->getSelect()->joinLeft(
                            array("auto" => $this->_getResources()->getTableName('hautopart/combination_list')),
                            "e.entity_id = auto.product_id",array('auto_make' => 'auto.make'));
                        $collection->getSelect()->where("auto.make={$this->_ymmParams['make']}");

                        break;

                    case 'model':

                        $collection->getSelect()->joinLeft(
                            array("auto_model" => $this->_getResources()->getTableName('hautopart/combination_list')),
                            "e.entity_id = auto_model.product_id AND auto_model.make = {$this->_ymmParams['make']}",array('auto_model' => 'auto_model.model'));
                        $collection->getSelect()->where("auto_model.model={$this->_ymmParams['model']}");

                        break;
                }

            }

            $collection->getSelect()->distinct('e.entity_id');

            foreach ($collection as $product) {
                $partName =  $product->getPartName();
                $autoType = $product->getAttributeText('auto_type');

                if(empty($partName) || empty($autoType)) {
                    continue;
                }

                if(is_array($autoType)) {
                    foreach ($autoType as $itemAutoType) {
                        if(!isset($this->_autoPartsData[$itemAutoType][$partName]))
                            $this->_autoPartsData[$itemAutoType][$partName] = $this->_resultArray($product);
                    }
                }else {
                    if(!isset($this->_autoPartsData[$autoType][$partName]))
                        $this->_autoPartsData[$autoType][$partName] = $this->_resultArray($product);
                }
            }
            $websiteId = Mage::app()->getStore()->getWebsiteId();
            if($websiteId == 2) {
                $this->_autoPartsData['Subaru Gear & Accessories'] = array(
                    'Subaru Gear' => array(
                        'name' => 'Subaru Gear',
                        'image_url' => Mage::getBaseUrl('media') . 'hautopart/s/u/subaru_gear.jpg',
                        'link' => Mage::getBaseUrl() . 'part/subaru-gear.html'
                    )
                ); 
            }

            unset($this->_autoPartsData['Ford Performance Parts']);
            unset($this->_autoPartsData['Roush Performance Parts']);

            ksort($this->_autoPartsData); 
            $this->_collection = $this->_autoPartsData;
        }


        return $this->_collection;
    }

    /**
     * @param $product
     * @return array
     */
    protected function _resultArray($product)
    {
        $_helper = Mage::helper('hauto/path');
        $controller = 'part-make';
        $autoNameUrl = array();
        $autoNameUrl[1] = $_helper->filterTextToUrl($this->_makeLabel);
        $autoNameUrl[3] = $_helper->filterTextToUrl($product->getPartName());
        if(!empty($this->_modelLabel)) {
            $controller = 'part-model';
            $autoNameUrl[2] = $_helper->filterTextToUrl($this->_modelLabel);
        }

        if(!empty($this->_yearLabel)) {
            $controller = 'part-ymm';
            $autoNameUrl[0] = $_helper->filterTextToUrl($this->_yearLabel);
        }
        ksort($autoNameUrl);

       return array(
            'name' => $product->getPartName(),
            'image_url' => $this->_reSizeImage($product, 210, 180),
            'link' => $_helper->generateLink(implode('-',$autoNameUrl),$controller),
        );
    }


    protected function _reSizeImage($product, $width, $height)
    {
        return (string)Mage::helper('catalog/image')->init($product, 'image')
            ->resize($width, $height)
            ->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE);
    }

    /**
     * @param $categoryUrl
     * @return mixed
     */
    public function getCategoryUrl($categoryUrl, $isYMM = false)
    {
        if(!$isYMM) {
            $_helper = Mage::helper('hauto/path');
            $filterText =  $_helper->filterTextToUrl($categoryUrl);
            return $_helper->generateLink($filterText,'category');

        }else {

            $filterText =  $this->scrubLabel($categoryUrl);
            return Mage::helper('hautopart/parser')->getLink($filterText,'category');
        }
    }

    public function scrubLabel($label)
    {
        $conditions = array(
            array(
                'needle' => '&',
                'replace' => 'and'
            ),
//            array(
//                'needle'    => '/',
//                'replace'   => 'and'
//            ),
            array(
                'needle' => '-',
                'replace' => ''
            )
        );
        foreach ($conditions as $condition) {
            $label = str_replace($condition['needle'], $condition['replace'], $label);
        }
        $parts = explode(' ', $label);
        $parts = array_filter($parts);
        $label = implode(' ', $parts);
        return $label;

    }

}
