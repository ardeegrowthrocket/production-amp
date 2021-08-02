<?php

class MagicToolbox_Sirv_Model_Observer
{
    protected $isSirvEnabled = false;

    protected $useSirvImageProcessing = false;

    protected $bucket = '';

    protected $sirvAdapter = null;

    protected $mediaConfig = null;

    protected $images = array();

    public function __construct()
    {
        $dataHelper = Mage::helper('sirv');
        $this->isSirvEnabled = $dataHelper->isSirvEnabled();
        $this->useSirvImageProcessing = $dataHelper->isSirvImageProcessingEnabled();
        $this->bucket = $dataHelper->getStoreConfig('sirv/s3/bucket');
        $this->sirvAdapter = Mage::getSingleton('sirv/adapter_s3');
        $this->mediaConfig = Mage::getSingleton('catalog/product_media_config');
    }

    public function onSirvConfigChange($observer)
    {
        if (!$this->sirvAdapter->isAuth()) {
            $configData = Mage::getSingleton('adminhtml/config_data');
            Mage::getConfig()->saveConfig('sirv/general/enabled', '0', $configData->getScope(), $configData->getScopeId());
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Your Sirv S3 access credentials were rejected. Please check and try again.');
            return;
        }

        if (!$this->sirvAdapter->isEnabled()) {
            $configData = Mage::getSingleton('adminhtml/config_data');
            Mage::getConfig()->saveConfig('sirv/general/enabled', '0', $configData->getScope(), $configData->getScopeId());
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('The bucket name you provided (' . $this->bucket . ') is not available. You must enter a proper bucket name to use Sirv.');
            return;
        }

        if (!$this->isSirvEnabled) {
            return;
        }

        //NOTE: if we need to clear cache and files from Sirv, do this only for option
        //      Optimize from originals
        //      and maybe for option
        //      Use Magento watermark
        // Mage::helper('sirv/cache')->clearCache();
    }

    public function onCatalogProductSaveBefore($observer)
    {
        if (!$this->useSirvImageProcessing) {
            return;
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product instanceof Mage_Catalog_Model_Product) {
            return;
        }

        $this->images = $this->getProductImages($product->getId());
    }

    public function onCatalogProductSaveAfter($observer)
    {
        if (!$this->useSirvImageProcessing) {
            return;
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product instanceof Mage_Catalog_Model_Product) {
            return;
        }

        $images = $this->getProductImages($product->getId());

        $removed = array_diff($this->images, $images);
        foreach ($removed as $file) {
            $url = '/' . $this->mediaConfig->getMediaShortUrl($file);
            //NOTE: remove file from sirv and cache
            $this->sirvAdapter->remove($url);
        }

        $added = array_diff($images, $this->images);
        foreach ($added as $file) {
            $path = $this->mediaConfig->getMediaPath($file);
            if (!file_exists($path)) {
                //NOTE: display notice here!?
                continue;                
            }
            $url = '/' . $this->mediaConfig->getMediaShortUrl($file);
            //NOTE: add file to sirv and cache
            $this->sirvAdapter->save($url, $path);
        }

        //NOTE: check for rest images
        $images = array_diff($images, $added);
        $cacheHelper = Mage::helper('sirv/cache');
        foreach ($images as $file) {
            $path = $this->mediaConfig->getMediaPath($file);
            if (!file_exists($path)) {
                //NOTE: display notice here?
                continue;
            }
            $modificationTime = filemtime($path);
            $url = '/' . $this->mediaConfig->getMediaShortUrl($file);
            if (!$cacheHelper->isCached($url, $modificationTime)) {
                //NOTE: add file to sirv and update cache
                $this->sirvAdapter->save($url, $path);
            }
        }
    }

    public function getProductImages($productId)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readAdapter */
        $readAdapter = $resource->getConnection('catalog_read');
        $table = $resource->getTableName('catalog/product_attribute_media_gallery');
        /** @var Varien_Db_Select $select */
        $select = $readAdapter->select()
            ->from($table, array('value'))
            ->where('entity_id = :entity_id');
        $images = $readAdapter->fetchCol($select, array('entity_id' => $productId));
        return $images;
    }

    public function validateCache($schedule)
    {
        if (!$this->isSirvEnabled) {
            return;
        }

        $mediaPath = Mage::getBaseDir('media');
        $cacheHelper = Mage::helper('sirv/cache');
        $collection = $cacheHelper->getCollection();

        foreach ($collection->getIterator() as $record) {
            $url = $record->getData('url');
            $timestamp = $record->getData('modification_time');

            if ($this->useSirvImageProcessing) {
                $filePath = $mediaPath . str_replace('/', DS, $url);
            } else {
                $filePath = preg_replace('#^(/catalog/product)/cache/.*?(/[^/]/[^/]/[^/]+?\.[a-zA-Z0-9]++)$#', '$1$2', $url);
                $filePath = $mediaPath . str_replace('/', DS, $filePath);
            }

            if (!file_exists($filePath)) {
                //NOTE: file does not exist localy so remove it from sirv
                $this->sirvAdapter->remove($url);//NOTE: remove url (because of cache pathes)
                continue;
            }

            $modificationTime = filemtime($filePath);
            if ($timestamp < $modificationTime) {
                //NOTE: file was changed so reupload file to sirv
                if ($this->useSirvImageProcessing) {
                    $this->sirvAdapter->save($url, $filePath);
                } else {
                    //NOTE: we can't reupload the cached file because it does not exist
                    $r = $this->sirvAdapter->remove($url);
                }
            }
        }
    }
}
