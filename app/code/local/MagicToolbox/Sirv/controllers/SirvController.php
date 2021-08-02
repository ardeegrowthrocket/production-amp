<?php

class MagicToolbox_Sirv_SirvController extends Mage_Adminhtml_Controller_Action
{
    protected $dataHelper = null;

    protected $cacheHelper = null;

    protected $sirvAdapter = null;

    protected $mediaConfig = null;

    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * Controller predispatch method
     *
     * @return Mage_Adminhtml_System_ConfigController
     */
    public function preDispatch()
    {
        parent::preDispatch();

        //NOTICE: do this here (not in '_construct' function) because 'admin' area was not ready before
        $this->dataHelper = Mage::helper('sirv');
        $this->cacheHelper = Mage::helper('sirv/cache');
        $this->sirvAdapter = Mage::getSingleton('sirv/adapter_s3');
        $this->mediaConfig = Mage::getSingleton('catalog/product_media_config');
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $response = new Varien_Object();
        return $this->getResponse()->setBody($response->toJson());
    }

    /**
     * Check whether vat is valid
     *
     * @return void
     */
    public function flushAction()
    {
        $success = false;
        if ($this->sirvAdapter->isEnabled()) {
            $success = $this->sirvAdapter->clearCache();
        }

        /** @var $coreHelper Mage_Core_Helper_Data */
        $coreHelper = Mage::helper('core');
        $body = $coreHelper->jsonEncode(array(
            'success' => $success
        ));
        $this->getResponse()->setBody($body);
    }

    /**
     * Check whether vat is valid
     *
     * @return void
     */
    public function synchronizeAction()
    {
        $success = false;
        if ($this->sirvAdapter->isEnabled()) {
            $this->synchronizeMediaGallery();
            $success = true;
        }

        /** @var $coreHelper Mage_Core_Helper_Data */
        $coreHelper = Mage::helper('core');
        $body = $coreHelper->jsonEncode(array(
            'success' => $success
        ));
        $this->getResponse()->setBody($body);
    }

    /**
     * Method to synchronize media gallery
     *
     * @return void
     */
    public function synchronizeMediaGallery()
    {
        if (!$this->dataHelper->isSirvImageProcessingEnabled()) {
            //NOTE: we can't synchronize media gallery in this case because cached files don't exist in file system
            return;
        }

        if (!$this->sirvAdapter->isEnabled()) {
            return;
        }

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readAdapter */
        $readAdapter = $resource->getConnection('catalog_read');
        $table = $resource->getTableName('catalog/product_attribute_media_gallery');
        /** @var Varien_Db_Select $select */
        $select = $readAdapter->select()->from($table, array('value'));
        $images = $readAdapter->fetchCol($select, array());

        foreach ($images as $file) {
            $path = $this->mediaConfig->getMediaPath($file);
            $shortUrl = '/' . $this->mediaConfig->getMediaShortUrl($file);
            if (!file_exists($path)) {
                if ($this->cacheHelper->isCached($shortUrl)) {
                    $this->sirvAdapter->remove($shortUrl);
                }
                continue;
            }

            $modificationTime = filemtime($path);
            if (!$this->cacheHelper->isCached($shortUrl, $modificationTime)) {
                $this->sirvAdapter->save($shortUrl, $path);
            }
        }
    }

    /**
     * Check is allowed access to action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config');
    }
}
