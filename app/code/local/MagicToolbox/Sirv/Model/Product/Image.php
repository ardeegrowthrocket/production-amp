<?php

class MagicToolbox_Sirv_Model_Product_Image extends Mage_Catalog_Model_Product_Image
{
    protected $isSirvEnabled = false;

    protected $useSirvImageProcessing = true;

    protected $useMagentoWatermark = false;

    protected $sirvAdapter = null;

    protected $cacheHelper = null;

    protected $baseMediaPath = '';

    protected $dbFileStorage = null;

    protected function _construct()
    {
        parent::_construct();
        $dataHelper = Mage::helper('sirv');
        $this->isSirvEnabled = $dataHelper->isSirvEnabled();
        $this->useSirvImageProcessing = $dataHelper->isSirvImageProcessingEnabled();
        $this->useMagentoWatermark = (bool)$dataHelper->getStoreConfig('sirv/general/magento_watermark');
        $this->sirvAdapter = Mage::getSingleton('sirv/adapter_s3');
        $this->cacheHelper = Mage::helper('sirv/cache');
        $this->baseMediaPath = Mage::getBaseDir('media');
        $this->dbFileStorage = Mage::helper('core/file_storage_database');
    }

    public function getImageProcessor()
    {
        if ($this->isSirvEnabled && !$this->_processor) {
            $this->_processor = Mage::getModel('sirv/varien_image', $this->getBaseFile());
        }

        return parent::getImageProcessor();
    }

    public function setWatermark($file, $position = null, $size = null, $width = null, $heigth = null, $imageOpacity = null)
    {
        if ($this->useMagentoWatermark) {
            parent::setWatermark($file, $position, $size, $width, $heigth, $imageOpacity);
        }

        return $this;
    }

    public function saveFile()
    {
        if ($this->isSirvEnabled && $this->useSirvImageProcessing) {
            $fileName = $this->getBaseFile();
            $this->getImageProcessor()->save($fileName);
            $this->dbFileStorage->saveFile($fileName);
            return $this;
        }

        return parent::saveFile();
    }

    public function getUrl()
    {
        if ($this->isSirvEnabled) {
            if ($this->useSirvImageProcessing) {
                $url = $this->sirvAdapter->getUrl($this->_baseFile);
                $url .= $this->getImageProcessor()->getImagingOptionsQuery();
            } else {
                $url = $this->sirvAdapter->getUrl($this->_newFile);
            }

            return $url;
        }

        return parent::getUrl();
    }

    public function isCached()
    {
        if ($this->isSirvEnabled) {
            $modificationTime = filemtime($this->_baseFile);
            if ($this->useSirvImageProcessing) {
                $sirvFileName = str_replace($this->baseMediaPath, '', $this->_baseFile);
                return $this->cacheHelper->isCached($sirvFileName, $modificationTime);
            } else {
                $sirvFileName = str_replace($this->baseMediaPath, '', $this->_newFile);
                return $this->cacheHelper->isCached($sirvFileName, $modificationTime);
            }
        }

        return parent::isCached();
    }

    public function clearCache()
    {
        parent::clearCache();
        if ($this->sirvAdapter->isEnabled()) {
            $this->sirvAdapter->clearCache();
        }
    }
}
