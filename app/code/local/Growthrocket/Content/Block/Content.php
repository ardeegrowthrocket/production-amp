<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/18
 * Time: 1:47 PM
 */

class Growthrocket_Content_Block_Content extends Mage_Core_Block_Abstract{
    /** @var Growthrocket_Content_Helper_Data $helper */
    protected $helper;

    /** @var Growthrocket_Content_Helper_Sql $helper */
    protected $sqlHelper;

    protected function _construct(){
        $this->helper = Mage::helper('grcontent');
        $this->sqlHelper = Mage::helper('grcontent/sql');
        $this->setCacheLifetime(false);
    }
    protected function _toHtml(){
        $contentId = $this->getId();
        $html = '';
        if($contentId){
            $content = Mage::getModel('grcontent/content')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($contentId);

            if($content && $content->getId()){
                $processor = $this->getTemplateProcessor();
                $html = $processor->filter($content->getContent());
            }
        }
        return $html;
    }
    protected function getTemplateProcessor(){
        return Mage::helper('grcontent')->getCategoryTemplateProcessor();
    }

    protected function getFitmentParams(){
        return unserialize($this->getRequest()->getParam('ymm_params',null));
    }

    public function getCacheKeyInfo()
    {
        $blockId = $this->getId();
        if ($blockId) {
            $result = array(
                'GRCONTENT_CONTENT',
                $blockId,
                Mage::app()->getStore()->getCode(),
            );
        } else {
            $result = parent::getCacheKeyInfo();
        }
        return $result;
    }
}