<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 04/10/2018
 * Time: 1:33 PM
 */

class Growthrocket_Content_Block_Content_Part extends Growthrocket_Content_Block_Content{
    const FITMENT_PARAM_PART = 'part';

    private $fitment = array();

    protected function _construct(){
        parent::_construct();
    }
    protected function _beforeToHtml()
    {
        $this->fitment = $this->getFitmentParams();
        $requestPath = substr($this->getRequest()->getRequestString(),1);
        $storeId = Mage::app()->getStore()->getId();
        $contentId = $this->sqlHelper->getPageContent($requestPath, self::FITMENT_PARAM_PART, $storeId);
        if($contentId){
            $this->setId($contentId);
        }else{
            $defaultContentId = Mage::getStoreConfig('grcontent/part/content');
            $this->setId($defaultContentId);
        }
        return $this; // TODO: Change the autogenerated stub
    }

    protected function getTemplateProcessor()
    {
        $processor = $this->helper->getPartTemplateProcessor();
        $processor->setFitment($this->fitment);
        return $processor;
    }
}