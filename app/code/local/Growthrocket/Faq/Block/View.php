<?php

class Growthrocket_Faq_Block_View extends Mage_Core_Block_Template
{
    protected $_currentPart;

    protected $_collection;

    protected function _prepareLayout()
    {
        $this->getResult();
        return parent::_prepareLayout();
    }

    public function getResult()
    {

        if(!$this->_collection) {
            $storeId = $store = Mage::app()->getStore()->getId();
            $params = Mage::app()->getRequest()->getParams();
            $faqArray = array();
            if (isset($params['ymm_params'])) {
                $ymm = unserialize($params['ymm_params']);
                if (isset($ymm['part'])) {
                    $this->_currentPart = $ymm['part'];
                    $collection = Mage::getModel("faq/grfaq")->getCollection();
                    $collection->addFieldToSelect('*');
                    $collection->addFieldToFilter('page_type', $ymm['part']);
                    $collection->addFieldToFilter('store_ids', ['finset' => $storeId]);
                    $collection->addFieldToFilter('store_ids', ['finset' => $storeId]);
                    $collection->getSelect()->order('parent ASC');
                    $collection->getSelect()->order('position ASC');

                    if (!empty($collection->getSize())) {
                        foreach ($collection as $item) {

                            if ($item->getParent() > 0) {
                                $faqArray[$item->getParent()][] = $item->getData();
                            } else {
                                $faqArray[$item->getId()][] = $item->getData();
                            }
                        }
                    }
                }
            }
            $this->_collection = $faqArray;
        }

        return $this->_collection;
    }

    public function getPartName()
    {
        return $this->_currentPart;
    }

    /**
     * @param $id
     * @return string
     */
    public function getAnchorId($id)
    {
        return 'anchor-faq-' . $id;
    }

}