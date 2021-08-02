<?php

class MagicToolbox_Sirv_Helper_Image extends Mage_Catalog_Helper_Image
{
    protected $dataHelper = null;

    protected $isSirvEnabled = false;

    protected $useSirvImageProcessing = false;

    public function __construct()
    {
        $this->dataHelper = Mage::helper('sirv');
        $this->isSirvEnabled = $this->dataHelper->isSirvEnabled();
        $this->useSirvImageProcessing = $this->dataHelper->isSirvImageProcessingEnabled();
    }

    public function __toString()
    {
        if (!$this->isSirvEnabled) {
            return parent::__toString();
        }

        if (!$this->useSirvImageProcessing) {
            return parent::__toString();
        }

        try {
            $model = $this->_getModel();

            if ($this->getImageFile()) {
                $model->setBaseFile($this->getImageFile());
            } else {
                $model->setBaseFile($this->getProduct()->getData($model->getDestinationSubdir()));
            }

            if ($this->_scheduleRotate) {
                $model->rotate($this->getAngle());
            }

            if ($this->_scheduleResize) {
                $model->resize();
            }

            if ($this->getWatermark()) {
                $model->setWatermark($this->getWatermark());
            }

            if ($model->isCached()) {
                $url = $model->getUrl();
            } else {
                $url = $model->saveFile()->getUrl();
            }
        } catch (Exception $e) {
            $url = Mage::getDesign()->getSkinUrl($this->getPlaceholder());
        }

        return $url;
    }
}
