<?php

class Homebase_Autopart_Block_Category_ListingYmm extends Mage_Core_Block_Template
{

    protected $_collection;

    protected $_ymmParams;

    protected $_makeLabel = "";

    protected $_modelLabel = "";

    protected $_yearLabel = "";

    protected $_autoPartsData = array();

    protected $_placeholderImage  = '';


    protected function _construct()
    {

        $this->_ymmParams = unserialize($this->getRequest()->getParam('ymm_params'));
        $this->_placeholderImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product/placeholder/' . Mage::getStoreConfig('catalog/placeholder/image_placeholder');
        parent::_construct();
    }

    /**
     * @return Mage_Core_Block_Abstract
     * @throws Mage_Core_Model_Store_Exception
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

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $autoPartTable = 'auto_part_listing';
            $websiteId = $this->_getStore()->getWebsite()->getId();

            $includedParam = array('year','make','model');
            $condition = "";
            foreach ($this->_ymmParams as $key => $value){
                if(in_array($key, $includedParam) && !empty($value)){
                    $condition  .= " AND {$key} = {$value}";
                }
            }

            $query = "SELECT * FROM {$autoPartTable} WHERE website_id = {$websiteId} {$condition} ORDER BY category ASC, part_name ASC";

            $results = $readConnection->fetchAll($query);

            foreach ($results as $item){
                $partName =  $item['part_name'];
                $autoType =  $item['category'];

                $this->_autoPartsData[$autoType][$partName] = $this->_resultArray($item, $websiteId);
            }

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
    protected function _resultArray($item, $websiteId)
    {
        $_helper = Mage::helper('hauto/path');
        $controller = 'part-make';
        $autoNameUrl = array();
        $autoNameUrl[1] = $_helper->filterTextToUrl($this->_makeLabel);
        $autoNameUrl[3] = $_helper->filterTextToUrl($item['part_name']);
        if(!empty($this->_modelLabel)) {
            $controller = 'part-model';
            $autoNameUrl[2] = $_helper->filterTextToUrl($this->_modelLabel);
        }

        if(!empty($this->_yearLabel)) {
            $controller = 'part-ymm';
            $autoNameUrl[0] = $_helper->filterTextToUrl($this->_yearLabel);
        }

        ksort($autoNameUrl);
        $var = unserialize($item['var']);
        if(!empty($var['image']) && $var['image'] != 'no_selection'){
            $imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($var['image']);
            //$imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "ymm-images/{$websiteId}/{$var['image']}";
        }else{
            $imageUrl = $this->_placeholderImage;
        }

        return array(
            'name' => $item['part_name'],
            'image_url' => $imageUrl, //$this->_reSizeImage($product, 210, 180),
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