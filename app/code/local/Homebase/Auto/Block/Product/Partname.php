<?php
class Homebase_Auto_Block_Product_Partname extends Mage_Core_Block_Template{

    protected $_collection;

    protected $_categoryId;

    protected $_partNames = array();

    protected $_imageWidth = 163;

    protected $_imageHeight = 105;

    protected $_helper;

    protected $_placeholderImage  = '';

    /**
     * @throws Exception
     */
    protected function _construct()
    {
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $this->_categoryId = $params['category'];
        $this->_helper = Mage::helper('hautopart');
        $this->_placeholderImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product/placeholder/' . Mage::getStoreConfig('catalog/placeholder/image_placeholder');

        parent::_construct();
    }

    protected function _prepareLayout()
    {
        $this->getCollection();
        return parent::_prepareLayout();
    }

    /**
     * @return array|mixed
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Cache_Exception
     */
    public function getCollection()
    {

        $_store = Mage::app()->getStore();
        $cacheId = 'category_partname_new_' . $this->_categoryId . '-' . $_store->getId();

        if(!$this->_collection) {
            if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
                $this->_partNames = unserialize($data_to_be_cached);

            } else {
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $autoPartTable = 'auto_part_listing';
                $websiteId = Mage::app()->getStore()->getWebsite()->getId();

                $queryLabel = "SELECT label FROM auto_combination_list_labels WHERE `option` = {$this->_categoryId} LIMIT 1";
                $categoryLabel = $readConnection->fetchCol($queryLabel);

                if(!empty($categoryLabel)){
                    $query = "SELECT part_name,var FROM {$autoPartTable} WHERE website_id = {$websiteId} AND category = '{$categoryLabel['0']}' GROUP BY part_name  ORDER BY part_name ASC";
                    $results = $readConnection->fetchAll($query);

                    foreach ($results as $item) {
                        $partName =  $item['part_name'];
                        $var = unserialize($item['var']);
                        $image = isset($var['image']) ? $var['image'] : '';

                        if(!empty($var['image']) && $var['image'] != 'no_selection'){
                            $imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($var['image']);
                        }else{
                            $imageUrl = $this->_placeholderImage;
                        }

                        $this->_partNames[$partName] = array(
                            'label' => $partName,
                            'image' => $imageUrl,
                            'url' => $this->getBaseUrl() . 'part/' . $this->_helper->scrubLabel(strtolower($partName)) . '.html'
                        );
                    }
                }

                Mage::app()->getCache()->save(serialize($this->_partNames), $cacheId, array(), ((60 * 60) * 24) * 7);
            }

            if(empty(Mage::registry('sub_category_meta'))){
                Mage::register('sub_category_meta',array_slice($this->_partNames, 0, 3), true);
            }
            $this->_collection = $this->_partNames;

        }

        return $this->_collection;
    }

    /**
     * @param $product
     * @return string
     */
    protected function _reSizeImage($product)
    {
       return (string)Mage::helper('catalog/image')->init($product, 'small_image')
                    ->resize($this->_imageWidth, $this->_imageHeight)
                    ->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE);
    }

    /**
     * @throws Zend_Date_Exception
     */
    protected function _getDate()
    {
        return new Zend_Date();
    }

    public function getTitle()
    {
        return Mage::helper('hauto/path')->getRawOptionText('category',$this->_categoryId);
    }
}