<?php

class Homebase_Autopart_Block_Brand extends Mage_Core_Block_Template {

    const ROUSH_CONFIG_DATA = 'hautopart/lfp_performance/roush_category';

    const FORD_CONFIG_DATA = 'hautopart/lfp_performance/ford_category';

    protected $_type;


    public function _construct()
    {
        parent::_construct();

    }
    protected function _beforeToHtml()
    {
        $this->_type = $this->getData('cat_type');
        return parent::_beforeToHtml();
    }

    public function getCollection()
    {
        $categories = "";
        switch ($this->_type){
            case 'ford':
                $categories = $this->_getConfigData(self::FORD_CONFIG_DATA);
            break;
            case  'roush':
                $categories = $this->_getConfigData(self::ROUSH_CONFIG_DATA);
            break;
        }

        if(!empty($categories)){
            $categoryIds = explode(',',$categories);
          return  $this->_getCollection($categoryIds);
        }
    }

    protected function _getCollection($categoryIds = array())
    {

        $categoryArray = array();

        $cacheId = 'lfp_brand_page_' . $this->_type;

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $categoryArray = unserialize($data_to_be_cached);

        } else {
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'auto_type');
            $allOptions = $attribute->getSource()->getAllOptions(true, true);

            foreach ($allOptions as $instance) {
                $catId = $instance['value'];
                $catLabel = $instance['label'];

                if(in_array($catId, $categoryIds)){
                    $categoryArray[$catId] = array(
                        'id' => $catId,
                        'label' => $catLabel,
                        'link' =>  $this->getLink($catId),
                        'image' =>  $this->getImage($catId)
                    );
                }
            }

            Mage::app()->getCache()->save(serialize($categoryArray), $cacheId);
        }

        return $categoryArray;
    }

    /**
     * Get Link
     * @param $value
     * @return mixed
     */
    protected function getLink($value){
        $_helper = Mage::helper('hauto/path');
        $urlFriendlyText = $_helper->getOptionText('category',$value);
        return $_helper->generateLink($urlFriendlyText,'category');
    }

    /**
     * @param $optionId
     * @param bool $isResize
     * @param int $width
     * @param int $height
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getImage($optionId, $isResize = false, $width = 300, $height = 300)
    {

        $webId = Mage::app()->getStore()->getWebsiteId();

        $imageCollection = Mage::getModel('hautopart/image')->getCollection();

        $imageCollection->addFieldToFilter('option_id', $optionId);
        $imageCollection->addFieldToFilter('website_id', $webId);
        $_image = $imageCollection->fetchItem();
        if($_image && $_image->getId()){

            if(!$isResize){
                return Mage::getBaseUrl('media') . 'hautopart' . $_image->getImgPath();
            }else{
                return  Mage::helper('hautopart/image')->reSize($_image->getImgPath(), $width, $height);
            }

        }else{
            $imageCollection = Mage::getModel('hautopart/image')->getCollection();

            $imageCollection->addFieldToFilter('option_id', $optionId);
            $imageCollection->addFieldToFilter('website_id', 1);
            $_image = $imageCollection->fetchItem();


            if($_image){
                if(!$isResize){
                    return Mage::getBaseUrl('media') . 'hautopart' . $_image->getImgPath();
                }else{
                    return  Mage::helper('hautopart/image')->reSize($_image->getImgPath(), $width, $height);
                }
            }

        }
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function _getConfigData($code)
    {
        return Mage::getStoreConfig($code);
    }

}