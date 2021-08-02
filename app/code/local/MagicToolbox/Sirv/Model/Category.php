<?php

class MagicToolbox_Sirv_Model_Category extends Mage_Catalog_Model_Category
{
    protected $isSirvEnabled = false;

    protected $sirvAdapter = null;

    protected $cacheHelper = null;

    protected $baseMediaPath = '';

    protected function _construct()
    {
        parent::_construct();
        $dataHelper = Mage::helper('sirv');
        $this->isSirvEnabled = $dataHelper->isSirvEnabled();
        $this->sirvAdapter = Mage::getSingleton('sirv/adapter_s3');
        $this->cacheHelper = Mage::helper('sirv/cache');
        $this->baseMediaPath = Mage::getBaseDir('media');
    }

    public function getImageUrl()
    {
        if ($this->isSirvEnabled && ($image = $this->getImage())) {
            $relPath = '/catalog/category/' . $image;
            $fullPath = $this->baseMediaPath . $relPath;
            if (!file_exists($fullPath)) {
                return parent::getImageUrl();
            }

            $modificationTime = filemtime($fullPath);
            if (!$this->cacheHelper->isCached($relPath, $modificationTime)) {
                $this->sirvAdapter->save($relPath, $fullPath);//NOTICE: call filemtime second time
            }

            return $this->sirvAdapter->getUrl($relPath);
        }

        return parent::getImageUrl();
    }
}
