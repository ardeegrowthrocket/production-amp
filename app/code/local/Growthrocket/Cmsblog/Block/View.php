<?php
class Growthrocket_Cmsblog_Block_View extends Mage_Core_Block_Template
{
    protected $_collection;

    /**
     * @return Mage_Core_Block_Abstract
     * @throws Exception
     */
    protected function _prepareLayout()
    {

        $this->getCollection();
        $blockHead = $this->getLayout()->getBlock('head');
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        if(!empty($this->_collection)){
            $blockHead->setTitle($this->_collection->getTitle());
            $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home"),
                "title" => $this->__("Home"),
                "link"  => Mage::getBaseUrl()
            ));

            $breadcrumbs->addCrumb("Blog", array(
                "label" => $this->__("Blog"),
                "title" => $this->__("Blog"),
                 "link"  => Mage::getBaseUrl() . $this->_getHelper()->getModulePath()
            ));

            $breadcrumbs->addCrumb("title", array(
                "label" => $this->_collection->getTitle(),
                "title" => $this->_collection->getTitle()
            ));
        }

        return parent::_prepareLayout();
    }

    /**
     * @return Mage_Core_Model_Abstract
     * @throws Exception
     */
    public function getCollection()
    {
        $blogId = $this->getRequest()->getParam('blog_id');
        if(!$this->_collection) {
            $this->_collection = Mage::getModel('cmsblog/cmsblog')->load($blogId);

        }
        return $this->_collection;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return Mage::helper('cms')->getBlockTemplateProcessor()->filter($this->_collection->getBody());
    }

    /**
     * @return false|string
     */
    public function getDateCreated()
    {
        $currentTimestamp = Mage::getModel('core/date')->timestamp($this->_collection->getCreatedDate());
        return date('Y-M-d H:i:s', $currentTimestamp);
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->_collection->getTitle();
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        return Mage::helper('cmsblog');
    }

}