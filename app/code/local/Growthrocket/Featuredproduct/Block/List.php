<?php   
class Growthrocket_Featuredproduct_Block_List extends Mage_Core_Block_Template{

    /**
     * get featured product collection
     */
    public function collection($key = 'model')
    {
        $collectionArray = array();
        $store = Mage::app()->getStore();
        $cacheId = 'lfp_featured_product_' . $store->getId();

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $collectionArray = unserialize($data_to_be_cached);

        } else {

            $collection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect(array('name', 'sku','description','short_description'))
                ->addAttributeToFilter('type_id', array('eq' => 'autopart'))
                ->addWebsiteFilter()
                ->addAttributeToFilter('featured', 1);

            $collection->getSelect()->join(array('combination' => 'auto_combination_list'), 'combination.product_id=e.entity_id', array('combination.*'));
            $collection->getSelect()->joinLeft(array('aul' => 'auto_combination_list_labels'), 'aul.option=combination.model', array('aul.label'));
            $collection->getSelect()->columns(
                array(
                    'entity_id' => new Zend_Db_Expr('combination.id'),
                ));
            $varDataLayer = [];
            foreach ($collection as $item) {

                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                if ($product) {
                    $makeLabel = Mage::Helper('hauto')->getAutoLabelById($item->getMake());
                    $modelLabel = str_replace('-','',$item->getLabel());
                    $concatLabel =  "{$makeLabel}-{$modelLabel}.html";
                    $modelUrl = Mage::getBaseUrl() . "model\\" . strtolower(str_replace(' ','-',$concatLabel));

                    $varDataLayer = [
                        "name" => $product->getName(),
                        "category" => $product->getAttributeText('auto_type'),
                        "brand" => mage::Helper('growthrocket_gtm')->getDefaultBrand(),
                        "id" => $product->getSku(),
                        "price" => Mage::getModel('directory/currency')->format($product->getFinalPrice(), array('display'=>Zend_Currency::NO_SYMBOL), false),
                        "list" => "Homepage Featured Products",
                        "url" => $product->getProductUrl(),
                    ];

                    if($key == 'model'){
                        $collectionArray[$item->getModel()]['model_label'] = ucwords($item->getLabel());
                        $collectionArray[$item->getModel()]['make_label'] = ucwords($makeLabel);
                        $collectionArray[$item->getModel()]['model_url'] = $modelUrl;
                        $collectionArray[$item->getModel()]['data'][$product->getId()] = array(
                            'name' => $product->getName(),
                            'sku' => $product->getSku(),
                            'url' => $product->getProductUrl(),
                            'description' => $this->_getShortDescription($product->getDescription()),
                            'image_url' => (string) Mage::helper('catalog/image')->init($product, 'image')->keepFrame(false)->constrainOnly(true),
                            'ga_tracking' => $varDataLayer

                        );
                    }else {

                        if(count($collectionArray[$makeLabel]['data']) < 4) {
                            $makeUrl = Mage::getBaseUrl() . "make\\" . strtolower(str_replace(' ','-',$makeLabel)) . '.html';
                            $collectionArray[$makeLabel]['make_label'] = ucwords($makeLabel);
                            $collectionArray[$makeLabel]['make_url'] = ucwords($makeUrl);
                            $collectionArray[$makeLabel]['data'][$product->getId()] = array(
                                'name' => $product->getName(),
                                'sku' => $product->getSku(),
                                'short_description' => $product->getShortDescription(),
                                'description' => $product->getDescription(),
                                'url' => $product->getProductUrl(),
                                'image_url' => (string) Mage::helper('catalog/image')->init($product, 'image')->keepFrame(false)->constrainOnly(true),
                                'ga_tracking' => $varDataLayer

                            );
                        }

                    }

                }
            }
            ksort($collectionArray);
            Mage::app()->getCache()->save(serialize($collectionArray), $cacheId);

        }

        return $collectionArray;

    }

    protected function _getShortDescription($string)
    {
        $string = strip_tags($string);
        if (strlen($string) > 150) {

            $stringCut = substr($string, 0, 150);
            $endPoint = strrpos($stringCut, ' ');

            $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
            $string .= '...';
        }
        return $string;
    }


}