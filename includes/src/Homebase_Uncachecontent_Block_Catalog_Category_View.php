<?php
class Homebase_Uncachecontent_Block_Catalog_Category_View extends Mage_Catalog_Block_Category_View
{
    public function getCmsBlockHtml()
    {
        if (!$this->getData('cms_block_html')) {
            $html = $this->getLayout()->createBlock('cms/block')
                ->setBlockId($this->getCurrentCategory()->getLandingPage())
                ->setCacheLifetime(null)
                ->toHtml();
            $this->setData('cms_block_html', $html);
        }
        return $this->getData('cms_block_html');
    }
}
			