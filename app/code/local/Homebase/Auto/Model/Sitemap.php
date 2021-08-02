<?php
/**
 * Created by PhpStorm.
 * User: oliver
 * Date: 7/23/2017
 * Time: 8:00 PM
 */

class Homebase_Auto_Model_Sitemap extends Mage_Sitemap_Model_Sitemap{
    public function generateXml()
    {
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $this->getPath()));

        if ($io->fileExists($this->getSitemapFilename()) && !$io->isWriteable($this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $io->streamOpen($this->getSitemapFilename());

        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

        $storeId = $this->getStoreId();
        $date    = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

        /**
         * Generate categories sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/category/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/category/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($storeId);
        $categories = new Varien_Object();
        $categories->setItems($collection);


        Mage::dispatchEvent('sitemap_categories_generating_before', array(
            'collection' => $categories
        ));
        foreach ($categories->getItems() as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                1.0
            );
            $io->streamWrite($xml);
        }
        unset($collection);

        /**
         * Generate products sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/product/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/product/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
        $products = new Varien_Object();
        $products->setItems($collection);
        Mage::dispatchEvent('sitemap_products_generating_before', array(
            'collection' => $products
        ));
        foreach ($products->getItems() as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                1.0
            );
            $io->streamWrite($xml);
        }
        unset($collection);

        /** SKU route */
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
        $_productCollection = Mage::getModel('catalog/product')->getCollection();

        foreach($_productCollection as $_product){
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . 'sku/'. $_product->getSku() . '.html'),
                $date,
                $changefreq,
                $priority
            );
            $io->streamWrite($xml);
        }
        unset($_productCollection);

        /**
         * Generate cms pages sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/page/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/cms_page')->getCollection($storeId);
        $exempt = explode(',',Mage::getStoreConfig('sitemap/fitment/exempt'));
        foreach ($collection as $item) {
            if(!in_array($item->getUrl(),$exempt)){
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($baseUrl . $item->getUrl()),
                    $date,
                    $changefreq,
                    0.8
                );
                $io->streamWrite($xml);
            }
        }
        unset($collection);


        /** Combination route */

        /** @var Homebase_Auto_Model_Resource_Index_Combination $_fitment */
        $_fitment = Mage::getResourceSingleton('hauto/index_combination');
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_fitment->getReadConnection();
        /** @var Varien_Db_Statement_Pdo_Mysql $_result */
        $_result = $_reader->select()
            ->from($_fitment->getMainTable())
            ->query();
        $results = $_result->fetchAll();
        foreach($results as $result){
            $route = $result['route'];
            if($route == 'partmake'){
                $route = 'part-make';
            }else if($route == 'partymm'){
                $route = 'part-ymm';
            }else if($route == 'partmodel'){
                $route = 'part-model';
            }

            $fitmentUrl = $route . '/' . $result['path'] . '.html';
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $fitmentUrl),
                $date,
                $changefreq,
                0.8
            );
            $io->streamWrite($xml);
        }

        // Sku Ymm route

        $_result = $_reader->select()
            ->from($_fitment->getMainTable())
            ->where('route = ?', 'year')
            ->query();

        $results = $_result->fetchAll();
        /** @var Mage_Core_Model_Resource $_resource */
        $_resource =  Mage::getSingleton('core/resource');

        foreach($results as $result){
            if(array_key_exists('combination',$result)) {
                $ymm = unserialize($result['combination']);
                $_select = $_reader->select()
                    ->from(array('fitment' => $_resource->getTableName('hautopart/combination_list')))
                    ->join(array('catalog' => $_resource->getTableName('catalog/product')), 'fitment.product_id=catalog.entity_id');

                foreach ($ymm as $ndx => $value) {
                    $_select->where($ndx . '=?', $value);
                }
                /** @var Varien_Db_Statement_Pdo_Mysql $query_results */
                $query_results = $_select->query();
                $matches = $query_results->fetchAll();
                foreach ($matches as $match) {
                    $path = $result['path'];
                    $sku = $match['sku'];
                    $xml = sprintf(
                        '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                        htmlspecialchars($baseUrl . 'sku-ymm/' . $path . '/' . $sku . '.html'),
                        $date,
                        $changefreq,
                        1.0
                    );
                    $io->streamWrite($xml);
                }
            }
        }
        $io->streamWrite('</urlset>');
        $io->streamClose();

        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }
}