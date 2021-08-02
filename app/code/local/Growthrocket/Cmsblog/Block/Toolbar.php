<?php
class Growthrocket_Cmsblog_Block_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('cmsblog/toolbar.phtml');
    }

    public function getPagerHtml()
    {
        $pagerBlock = $this->getLayout()->createBlock('page/html_pager');
        if ($pagerBlock instanceof Varien_Object)
        {

            $pagerBlock->setAvailableLimit($this->getAvailableLimit());
            $pagerBlock->setUseContainer(false)
                ->setShowPerPage(false)
                ->setShowAmounts(false)
                ->setLimitVarName($this->getLimitVarName())
                ->setPageVarName($this->getPageVarName())
                ->setLimit($this->getLimit())
                ->setCollection($this->getCollection());
            return $pagerBlock->toHtml();
        }
        return '';
    }


}
