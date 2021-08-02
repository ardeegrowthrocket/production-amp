<?php

class Growthrocket_Content_Block_Content_Category extends Growthrocket_Content_Block_Content{



    private $fitment = array();

    const FITMENT_PARAM_CATEGORY = 'category';

    protected function _construct(){
        parent::_construct();
    }
    protected function _beforeToHtml()
    {
        $this->fitment = $this->getFitmentParams();
        $requestPath = substr($this->getRequest()->getRequestString(),1);
        $storeId = Mage::app()->getStore()->getId();
        $contentId = $this->sqlHelper->getPageContent($requestPath, self::FITMENT_PARAM_CATEGORY, $storeId);
        //Manually set content id
        $this->setId($contentId);

        return $this;
    }
    protected function getTemplateProcessor(){
        $processor = $this->helper->getCategoryTemplateProcessor();
        $processor->setFitment($this->fitment);
        return $processor;
    }

}