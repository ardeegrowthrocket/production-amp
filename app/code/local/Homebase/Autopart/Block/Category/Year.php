<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/4/17
 * Time: 8:58 PM
 */

class Homebase_Autopart_Block_Category_Year extends Mage_Core_Block_Template implements Homebase_Autopart_Block_Category_CategoryInterface {

    /** @var  Homebase_Autopart_Helper_Parser $_helper */
    protected $_helper;

    /** @var  Homebase_Autopart_Helper_Data $_helper */
    protected $_dataHelper;

    /** @var  */
    protected $_collection;

    protected $_autoName;

    /** @var  Mage_Core_Model_Session */
    //protected $_session;

    const AUTO_TYPE_ATTRIBUTE_ID = 251;
    public function _construct(){
        parent::_construct();
        $this->_helper  = Mage::helper('hautopart/parser');
        $this->_dataHelper = Mage::helper('hautopart');
        //$this->_session = Mage::getSingleton('core/session');
    }
    
    protected function _prepareLayout(){
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $size = count($params);
        $labelArray = array();
        $name = "";

        $ctr = 0;
        if($breadcrumbs){
            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));

            foreach($params as $ndx=> $value){
                $ctr++;
                $label = $this->_helper->getLabel($value);

                if($ndx == 'model'){
                    $name = $this->_helper->getLabel($value,'name');
                }else {
                    $name = $label;
                }

                $labelArray[$ndx] = $name;
                if($ctr == $size) {
                    $label = implode(' ',$labelArray);
                }

                if($ndx == 'year') {
                    continue;
                }

                $breadcrumbs->addCrumb('ymm-' . $ndx, array(
                    'label' => ucwords($label),
                    'title' => $name,
                    'link'  => (($ctr < $size) ? $this->_helper->getLink($name,$ndx) : '')
                ));
            }
        }

        $this->getAutoName();
        $this->getList();
        $this->_setCustomMetaContent();

        return parent::_prepareLayout();
    }
    
  public function getList()
    {

        if(!$this->_collection) {

            // TODO: Implement getList() method.
            $params = unserialize($this->getRequest()->getParam('ymm_params'));

            /** @var Homebase_Autopart_Model_Resource_Combination_Collection $collection */
            $collection = Mage::getModel('hautopart/mix')->getCollection();
            foreach ($params as $key => $val) {
                $collection->addFieldToFilter($key, $val);
            }


            if ($collection->count() == 0)
                return;
            $productIds = $collection->getColumnValues('product_id');
//        $categories = array();
            $auto_categories = array();
            $universalProduct = Mage::helper('hautopart')->getUniversalProducts();
            $productIds = array_merge($productIds, $universalProduct);
            foreach ($productIds as $productId) {

                $productCategories = explode(',', $this->_getCategoryIds($productId));

                if (is_array($productCategories) && count($productCategories) == 1) {
                    array_push($auto_categories, array_pop($productCategories));
                } else {
                    $auto_categories = array_merge($auto_categories, $productCategories);
                }
//            /** @var Mage_Catalog_Model_Product $_product */
//            $_product = Mage::getModel('catalog/product')->load($productId);
//            $auto_categories = explode(',',$_product->getData('auto_type'));
//            foreach($auto_categories as $auto_category){
//                $categories[] = $auto_category;
//            }
            }
            $hautoHelper = Mage::helper('hautopart');
            $unq_categories = array_filter(array_unique($auto_categories));
            $categoryCollection = new Varien_Data_Collection();
            foreach ($unq_categories as $optionId) {
                $label = $this->_dataHelper->getOptionValue($optionId);
                if ($label && !is_null($label) && !$hautoHelper->isAutoTypeExcluded($optionId)) {
                    $scrubbedLabel = $this->scrubLabel($label);
                    $link = $this->_helper->getLink($scrubbedLabel, 'category');
                    $category = new Varien_Object();
                    $category->setOptionId($optionId);
                    $category->setOptionLabel($label);
                    $category->setScrubbedLabel($scrubbedLabel);
                    $category->setLink($link);
                    $categoryCollection->addItem($category);
                }
            }
            $this->_collection =  Mage::helper('hautopart')->sortGenericCollection($categoryCollection);
        }

        return $this->_collection;
    }

    protected function _setCustomMetaContent()
    {
        $data = array();
        $counter = 1;
        $collection = $this->_collection;
        if($this->_collection) {
            foreach ($collection as $item){

                if($counter <= 3) {
                    $data[] = $item->getOptionLabel();
                    $counter++;
                }else{
                    break;
                }
            }
        }
        Mage::register('ymm_meta_data', $data);
    }

    public function scrubLabel($label){
        $conditions = array(
            array(
                'needle'    => '&',
                'replace'   => 'and'
            ),
//            array(
//                'needle'    => '/',
//                'replace'   => 'and'
//            ),
            array(
                'needle'    => '-',
                'replace'   => ''
            )
        );
        foreach($conditions as $condition){
            $label  = str_replace($condition['needle'],$condition['replace'],$label);
        }
        $parts = explode(' ', $label);
        $parts = array_filter($parts);
        $label = implode(' ', $parts);
        return $label;
    }
    public function getAutoName()
    {
        if(!$this->_autoName){
            $params = unserialize($this->getRequest()->getParam('ymm_params'));
            foreach($params as $param){
                $ymm[] = $this->_helper->getLabel($param,'name');
            }

            $this->_autoName = implode(' ', $ymm);

            if(!Mage::registry('ymm_autoname')){
                Mage::register('ymm_autoname', $this->_autoName);
            }
        }

        return $this->_autoName;
    }
    public function getCustomLink($model){
        return $this->_helper->getLink($model,'category');
    }

    public function getImage($optionId, $isResize = false, $width = 300, $height = 300){

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
     * Retrieve category ids using native SQL for optimized run time
     * @param $productId
     * @return null
     */
    protected function _getCategoryIds($productId){
        $helper = Mage::helper('hautopart');

        $varcharTable = $helper->_getTable('catalog_product_entity_varchar');
        $select = $helper->_getReader()->select();

        $select->from($varcharTable,array('value'))
            ->where('entity_id= ? ',$productId)
            ->where('attribute_id= ?', self::AUTO_TYPE_ATTRIBUTE_ID);
        $result = $select->query()->fetchAll(PDO::FETCH_ASSOC, 0);
        if(count($result) === 1){
            $value = array_pop($result);
            return $value['value'];
        }elseif(count($result) >= 2){
            $value = $result[0];
            return $value['value'];
        }
        return null;
    }
}