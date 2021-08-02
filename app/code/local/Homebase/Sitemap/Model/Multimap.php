<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/10/17
 * Time: 8:34 PM
 */

class Homebase_Sitemap_Model_Multimap extends Mage_Core_Model_Abstract{

    protected $_eventPrefix = 'hsitemap_multimap';

    protected function _construct()
    {
        $this->_init('hsitemap/multimap');
    }

    protected function _beforeSave(){

        $io = new Varien_Io_File();
        $realPath = $io->getCleanPath(Mage::getBaseDir() . '/' . $this->getPath());
        if (!$io->allowedPath($realPath, Mage::getBaseDir())) {
            Mage::throwException(Mage::helper('hsitemap')->__('Please define correct path'));
        }

        if (!$io->fileExists($realPath, false)) {
            Mage::throwException(Mage::helper('hsitemap')->__('Please create the specified folder "%s" before saving the sitemap.', Mage::helper('core')->escapeHtml($this->getPath())));
        }

        if (!$io->isWriteable($realPath)) {
            Mage::throwException(Mage::helper('hsitemap')->__('Please make sure that "%s" is writable by web-server.', $this->getPath()));
        }

        if (!preg_match('#^[a-zA-Z0-9_\.]+$#', $this->getFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('Please use only letters (a-z or A-Z), numbers (0-9) or underscore (_) in the filename. No spaces or other characters are allowed.'));
        }
        if (!preg_match('#\.xml$#', $this->getFilename())) {
            $this->setFilename($this->getFilename() . '.xml');
        }

        $this->setPath(rtrim(str_replace(str_replace('\\', '/', Mage::getBaseDir()), '', $realPath), '/') . '/');

        return parent::_beforeSave();
    }

    public function generateXml(){
        $io = new Varien_Io_File();
        $folder = pathinfo($this->getFilename(),PATHINFO_FILENAME);
        $io->setAllowCreateFolders(true);
        $path = $this->convertToAbsolutePath($this->getPath() . $folder . '/');

        if($io->checkAndCreateFolder($path,0777)){
            if($io->fileExists($path) && !$io->isWriteable($path)){
                Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
            }

            $files = scandir($path);
            foreach($files as $file){
                if($file !== '.' && $file !=='..'){
                    $abspath = $path . $file;
                    $fileExt = pathinfo($file,PATHINFO_EXTENSION);
                    #if($fileExt !== 'xml'){
                    #$this->gzCompressFile($abspath);
                    chmod($abspath, 0777);
                    unlink($abspath);
                    #}
                }
            }



            try {

                $this->buildContentMap($path);
                $this->buildSkuMap($path);
                $this->buildCombinationmap($path);
            } catch (Exception $e){
                echo $e->getMessage();
                exit();
            }

//            $this->buildSkuCombinationMap($path);





            $files = scandir($path);
            $masterSitemap = $this->getPath();
            $abspathMasterSitemap = $this->convertToAbsolutePath($masterSitemap);
            $io->open(array('path' => $abspathMasterSitemap));
            $io->streamOpen($this->getFilename());

            $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>');
            $io->streamWrite('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
            $date    = Mage::getSingleton('core/date')->gmtDate('c');


            $storeid = $this->getStoreId();
            $baseUrl = Mage::app()->getStore($storeid)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            foreach($files as $file){
                if($file !== '.' && $file !=='..'){
                    $filePath =  $this->getPath() . $folder . '/'. $file;
                    $url = $baseUrl . $this->cleanUrl($filePath);
                    $xml = sprintf(
                        '<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>',$url,$date
                    );
                    $io->streamWrite($xml);
                }
            }
            $io->streamWrite('</sitemapindex>');
            $io->close();
        }
    }

    private function buildContentMap($path){
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));

        if($io->fileExists($path) && !$io->isWriteable($path)){
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $io->streamOpen('cms.xml');
        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

        $storeId = $this->getStoreId();
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $date    = Mage::getSingleton('core/date')->gmtDate('c');
        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

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

        $xml = sprintf(
            '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
            htmlspecialchars($baseUrl),
            $date,
            $changefreq,
            1.0
        );
        $io->streamWrite($xml);
        $io->streamWrite('</urlset>');
        $io->streamClose();
    }
    private function buildCombinationmap($path){

        $storeId = $this->getStoreId();
        /** @var Homebase_Auto_Model_Resource_Index_Combination $_fitment */
        $_fitment = Mage::getResourceSingleton('hauto/index_combination');
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_fitment->getReadConnection();
        /** @var Varien_Db_Select $_result */

        $fitmentTable = "fitment_route_store_{$storeId}";
        $routeResult = $_reader->fetchCol("SELECT route FROM {$fitmentTable} group by route");

        foreach ($routeResult as $routeVal){

            $select = $_reader->select()->from($fitmentTable)->where('route = ?',$routeVal);
            $page = 1;
            $pageCount = 5000;
            $doLoop = true;
            do{
                $select->limitPage($page, $pageCount);
                /** @var Varien_Db_Statement_Pdo_Mysql $result */
                $_result = $select->query();
                if($_result->rowCount() == 0){
                    $doLoop = false;
                    break;
                }
                $io = new Varien_Io_File();
                $io->setAllowCreateFolders(true);
                $io->open(array('path' => $path));

                if($io->fileExists($path) && !$io->isWriteable($path)){
                    Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
                }
                $suffix = '';
                if($page > 1){
                    $suffix = "_{$page}";
                }
                $io->streamOpen($routeVal . $suffix . '.xml');
                $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
                $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

                $storeId = $this->getStoreId();
                $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
                $date    = Mage::getSingleton('core/date')->gmtDate('c');
                $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

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
                    $urlpath2 = $result['path'];

                    if(trim($urlpath2) !== ""){
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

                }
                $io->streamWrite('</urlset>');
                $io->streamClose();
                $page++;
            }while($doLoop);
        }

    }
    private function buildSkuMap($path){
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
        $_productCollection = Mage::getModel('catalog/product')->getCollection();
        $_productCollection->setStoreId($this->getStoreId());
        $_productCollection->addAttributeToSelect(array('name','sku','store_ids','custom_url_key'));
        $_productCollection->addAttributeToFilter('status',1);

        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));

        if($io->fileExists($path) && !$io->isWriteable($path)){
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $io->streamOpen('sku.xml');
        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

        $storeId = $this->getStoreId();

        if(empty($storeId)){
            $storeId = 1;
        }
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $date    = Mage::getSingleton('core/date')->gmtDate('c');
        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

        foreach($_productCollection as $_product){
            if(in_array($storeId, $_product->getStoreIds())){
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($baseUrl . 'sku/'. $_product->getCustomUrlKey() . '.html'),
                    $date,
                    $changefreq,
                    1.0
                );
                $io->streamWrite($xml);

            }
        }
        $io->streamWrite('</urlset>');
        $io->streamClose();

        $_productCollection->clear();

        unset($_productCollection);
    }
    private function buildSkuCombinationMap($fpath){
        $_resource  = $this->getResource();
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getResource()->getReadConnection();
        $_select = $_reader->select()
            ->from($this->getResource()->getTable('hauto/combination_indexer'))
            ->where('route = ?', 'year');
        $_result = $_select->query();
        $results = $_result->fetchAll();

        $_multi = new Homebase_Sitemap_Model_Multimap_Index(array(
            'connection'    => $_reader,
            'resource'  => $this->getResource()
        ));


        foreach($results as $result){
            if(array_key_exists('combination',$result)) {
                $ymm = unserialize($result['combination']);
                $_select2 = $_reader->select()
                    ->from(array('fitment' => $_resource->getTable('hautopart/combination_list')))
                    ->join(array('catalog' => $_resource->getTable('catalog/product')), 'fitment.product_id=catalog.entity_id');
                foreach ($ymm as $ndx => $value) {
                    $_select2->where($ndx . '=?', $value);
                }
                /** @var Varien_Db_Statement_Pdo_Mysql $_matchResults */
                $_matchResults = $_select2->query();
                $matches = $_matchResults->fetchAll();
                foreach($matches as $match){
                    $path = $result['path'];
                    $sku = $match['sku'];
                    $url = 'sku-ymm/' . $path . '/' . $sku .'.html';
                    $storeId = $result['store_id'];
                    $data = array(
                        'sku_paths' => $url,
                        'store_id'  => $storeId
                    );
                    $_multi->insertToTemporaryTable($data);
                }
            }
        }

        $select = $_multi->fetchRoutes(0);
        $page = 1;
        $pageCount = 5000;
        $doLoop = true;
        do{
            $select->limitPage($page, $pageCount);
            /** @var Varien_Db_Statement_Pdo_Mysql $result */
            $_result = $select->query();
            if($_result->rowCount() == 0){
                $doLoop = false;
                break;
            }

            $io = new Varien_Io_File();
            $io->setAllowCreateFolders(true);
            $io->open(array('path' => $fpath));

            if($io->fileExists($fpath) && !$io->isWriteable($fpath)){
                Mage::throwException(Mage::helper('hsitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
            }
            $io->streamOpen('ymm_'.$page.'.xml');
            $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

            $storeId = $this->getStoreId();
            $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
            $date    = Mage::getSingleton('core/date')->gmtDate('c');
            $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

            $results = $_result->fetchAll();
            foreach($results as $result){
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($baseUrl . $result['sku_paths']),
                    $date,
                    $changefreq,
                    1.0
                );
                $io->streamWrite($xml);
            }
            $io->streamWrite('</urlset>');
            $io->streamClose();
            $page++;
        }while($doLoop);
    }
    protected function convertToAbsolutePath($path){
        $absolute_path = str_replace('//', '/', Mage::getBaseDir() .$path);
        return $absolute_path;
    }

    protected function cleanUrl($url){
        $absolute_path = str_replace('//', '/', $url);
        if(substr($absolute_path,0,1) ==='/'){
            $absolute_path = substr($absolute_path,1);
        }
        return $absolute_path;
    }
    protected function gzCompressFile($source, $level = 9){
        $dest = $source . '.gz';
        $mode = 'wb' . $level;
        $error = false;
        if ($fp_out = gzopen($dest, $mode)) {
            if ($fp_in = fopen($source,'rb')) {
                while (!feof($fp_in))
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        if ($error)
            return false;
        else
            return $dest;
    }

}