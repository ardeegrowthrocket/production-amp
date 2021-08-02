<?php
class Growthrocket_Relatedparts_IndexController extends Mage_Core_Controller_Front_Action{


    public function IndexAction()
    {

        if (!$this->_validateFormKey()) {
            echo "Invalid input";
            return;
        }

        $params = $this->getRequest()->getParams();
        $relatedProducts = array();

        if(isset($params['partname'])){

            $storeId = Mage::app()->getStore()->getStoreId();
            $partname = strtolower(str_replace(' ','-', $params['partname']));
            $cacheId = "cache_relatedparts_{$partname}_{$storeId}";

            if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
                $relatedProducts = unserialize($data_to_be_cached);

            } else {

                $collection = Mage::getResourceModel('catalog/product_collection')
                    ->addAttributeToSelect(array('name', 'small_image', 'amp_part_number', 'price', 'custom_url_key'))
                    ->addAttributeToFilter('part_name', $params['partname'])
                    ->addStoreFilter()
                    ->addFinalPrice()
                    ->addUrlRewrite()
                    ->setPageSize(5);

                foreach ($collection as $product) {
                    $relatedProducts['data'][] = array(
                        'product_id' => $product->getId(),
                        'name' => $product->getName(),
                        'price' => Mage::helper('core')->currency($product->getFinalPrice(), true, false),
                        'part_number' => $product->getAmpPartNumber(),
                        'image' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(224, 221)->constrainOnly(FALSE)->keepAspectRatio(TRUE)->keepFrame(TRUE),
                        'url' => $product->getProductUrl()
                    );
                }
                $relatedProducts['part_url'] = Mage::getBaseUrl() . 'part/' . strtolower(str_replace(' ', '-', $params['partname'])) . '.html';

                Mage::app()->getCache()->save(serialize($relatedProducts), $cacheId, array(), (60 * 60) * 24);
            }

                $this->_returnJson($relatedProducts);

        }

    }


    protected function _returnJson($data)
    {
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($data));
    }
}