<?php


require_once 'abstract.php';

class Generate_Fitment extends Mage_Shell_Abstract
{

    protected $_feedTitle = "fitment-url";
    protected $_store;
    protected $_fileExt = '.csv';
    protected $_storesIncluded = array(1,7);
    protected $_ymm = array();
    protected $_websites = array();
    protected $_currentWebsite;
    protected $_baseUrl;
    protected $_hautoHelper;
    protected $_currentTimeStamp;
    protected $_isFlatProductTableEnable = false;

    protected $_makeList = array();
    protected $_modelList = array();
    protected $_yearList = array();
    protected $_categoryList = array();
    protected $_partList = array();
    protected $_skuList = array();

    protected $_urlExt = '.html';

    public function run()
    {
        if(!empty($this->getArg('store'))){
            $this->_storesIncluded = array($this->getArg('store'));
        }

        if($this->getArg('deploy')) {

            try {
                set_time_limit(0);
                ini_set('memory_limit','-1');
                error_reporting(E_ALL);

                $this->_hautoHelper = Mage::helper('hautopart');
                $this->_initWebsites();
                $this->_generateYmm();
                $this->_isFlatProductTableEnable = Mage::helper('catalog/product_flat')->isEnabled();
                foreach (Mage::app()->getStores() as $store) {
                    $this->_store = $store;
                    $storeId = $this->_store->getId();

                    if (!in_array($storeId, $this->_storesIncluded)) {
                        continue;
                    }

                    $this->_setCurrentWebsite($store->getWebsiteId());
                    $filename = Mage::getBaseDir('base') . DS . 'feed' . DS . "{$this->_feedTitle}_{$this->_currentWebsite['code']}{$this->_fileExt}";
                    $csv = new Varien_File_Csv();
                    $csv->setDelimiter(",");
                    $csvData = array();

                    $csv->saveData($filename, $csvData);
                    $productData = $this->_getProductData($storeId);
                    $csv->saveData($filename, $productData);
                }

            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
        else{
            echo $this->usageHelp();
        }
    }

    /**
     * @param $websiteId
     * @return $this
     */
    protected function _setCurrentWebsite($websiteId)
    {
        if(array_key_exists($websiteId, $this->_websites)){
            $this->_currentWebsite = $this->_websites[$websiteId];
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _initWebsites()
    {
        $websites = Mage::app()->getWebsites();
        foreach ($websites as $website){
            $this->_websites[$website->getId()] = array(
                'id' => $website->getId(),
                'code' => $website->getCode(),
                'name' => $website->getName()
            );
        }
        $this->_websites[1]['code'] = 'amp';
        $this->_websites[6]['code'] = 'mgp';

        return $this;
    }

    /**
     * CSV Header Column
     * @return array
     */
    protected function _getFeedColumns()
    {
        $columns = array(
            'id',
            'name',
            'url', 
            'level',
            'brand'
        );

        return $columns;
    }
    protected function _getProductData($storeId)
    {

        $productData = array();
        $productData[] = $this->_getFeedColumns();
        Mage::app()->setCurrentStore($this->_store);
        $time1 = microtime(true);
        $collection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect(array('sku','name','part_name','custom_url_key','auto_type'))
                ->setStoreId($storeId)
                ->addAttributeToFilter('status','1')
                ->addMinimalPrice()
                ->addWebsiteFilter()
                ->addFinalPrice()
                ->addAttributeToSort('entity_id', 'desc');

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        $this->_currentTimeStamp = Mage::getModel('core/date')->timestamp(time());
        $this->_baseUrl = Mage::getBaseUrl();

        foreach ($collection as $product){
            
            echo "Execution on : " . $product->getSku() . PHP_EOL;
            $fitments = $this->_getYmmLabel($product);
            $partName = $product->getPartName();
            $category = $product->getAttributeText('auto_type');

            foreach ($fitments as  $ymm){

                $this->_getMakeList($ymm);
                $this->_getModelList($ymm);
                $this->_getYearList($ymm);

                if(is_array($category)){
                    foreach ($category as $item){
                        $this->_getCategoryList($item, $ymm);
                        $this->_getPartList($partName, $item, $ymm);
                        $this->_getSkuList($product, $partName, $item, $ymm);
                    }
                }else{
                    $this->_getCategoryList($category, $ymm);
                    $this->_getPartList($partName, $category, $ymm);
                    $this->_getSkuList($product, $partName, $category, $ymm);
                }
            }
        }

        $fitmentData = array_merge($this->_makeList, $this->_modelList, $this->_yearList, $this->_categoryList, $this->_partList, $this->_skuList);
        ksort($fitmentData);
        $productData = array_merge($productData,$fitmentData);

        $time2 = microtime(true);
        $totalTime = date('H:i:s', $time2 - $time1);
        echo "Script execution time on {$this->_currentWebsite['name']}: " . $totalTime . PHP_EOL;

        return $productData;
    }

    /**
     * @param $ymm
     */
    protected function _getMakeList($ymm)
    {
           $indx = $ymm['make'];
           $this->_makeList[$indx] = array(
               'id' => Mage::helper('hauto/path')->filterTextToUrl($indx),
               'name' => "{$ymm['make']}",
                'url' => $this->_getUrl('make', $ymm),
               'level' => 1,
               'brand' => $ymm['make']
           );
    }

    /**
     * @param $ymm
     */
    protected function _getModelList($ymm)
    {
        $indx = $ymm['make'] . $ymm['model'];
        $this->_modelList[$indx] = array(
            'id' => Mage::helper('hauto/path')->filterTextToUrl($indx),
            'name' => "{$ymm['make']} {$ymm['model']}",
            'url' => $this->_getUrl('model', $ymm),
            'level' => 2,
            'brand' => $ymm['make']
        );
    }

    /**
     * @param $ymm
     */
    protected function _getYearList($ymm)
    {
        $indx = $ymm['make'] . $ymm['model'] . $ymm['year'];
        $this->_yearList[$indx] = array(
            'id' => Mage::helper('hauto/path')->filterTextToUrl($indx),
            'name' => "{$ymm['year']} {$ymm['make']} {$ymm['model']}",
            'url' => $this->_getUrl('year', $ymm),
            'level' => 3,
            'brand' => $ymm['make']
        );
    }

    /**
     * @param $category
     * @param $ymm
     */
    protected function _getCategoryList($category, $ymm)
    {
        $indx = $ymm['make'] . $ymm['model'] . $ymm['year'] . $category;
        $ymm['category'] = Mage::helper('hauto/path')->filterTextToUrl($category);
        if(!empty($category)){
            $category = str_replace(',','', $category);
            $this->_categoryList[$indx] = array(
                'id' => Mage::helper('hauto/path')->filterTextToUrl($indx),
                'name' => "{$ymm['year']} {$ymm['make']} {$ymm['model']} {$category}",
                'url' => $this->_getUrl('category', $ymm),
                'level' => 4,
                'brand' => $ymm['make']
            );
        }
    }

    /**
     * @param $partName
     * @param $category
     * @param $ymm
     */
    protected function _getPartList($partName, $category, $ymm)
    {
        $indx = $ymm['make'] . $ymm['model'] . $ymm['year'] . $category . $partName;
        $ymm['category'] = Mage::helper('hauto/path')->filterTextToUrl($category);
        $ymm['partname'] = Mage::helper('hauto/path')->filterTextToUrl($partName);
        $category = str_replace(',','', $category);
        $this->_partList[$indx] = array(
            'id' => Mage::helper('hauto/path')->filterTextToUrl($indx),
            'name' => "{$ymm['year']} {$ymm['make']} {$ymm['model']} {$category} {$partName}",
            'url' => $this->_getUrl('part', $ymm),
            'level' => 5,
            'brand' => $ymm['make']
        );
    }

    /**
     * @param $product
     * @param $partName
     * @param $category
     * @param $ymm
     */
    protected function _getSkuList($product, $partName, $category, $ymm)
    {
        $sku = $product->getSku();
        $url = $product->getProductUrl();
        $indx = $ymm['make'] . $ymm['model'] . $ymm['year'] . $category . $partName . $sku;
        $category = str_replace(',','', $category);
        $sku = str_replace(',', ' - ', $sku);
        $this->_skuList[$indx] = array(
            'id' => Mage::helper('hauto/path')->filterTextToUrl($indx),
            'name' => "{$ymm['year']} {$ymm['make']} {$ymm['model']} {$category} {$partName} SKU:{$sku}",
            'url' => $url,
            'level' => 6,
            'brand' => $ymm['make']
        );
    }

    /**
     * @param $type
     * @param $ymm
     * @return string
     */
    protected function _getUrl($type, $ymm)
    {
        $year = $ymm['year'];
        $make = $this->_cleanUrl($ymm['make']);
        $model = $this->_cleanUrl($ymm['model']);
        switch ($type){
            case 'make':
                return $this->_baseUrl . 'make/' . $make . $this->_urlExt;
                break;
            case 'model':
                return $this->_baseUrl . 'model/' . "{$make}-{$model}"  . $this->_urlExt;
                break;
            case 'year':
                return $this->_baseUrl . 'year/' . "{$year}-{$make}-{$model}"  . $this->_urlExt;
            case 'category':
                return $this->_baseUrl . 'cat/' . "{$year}-{$make}-{$model}-{$ymm['category']}"  . $this->_urlExt;
            case 'part':
                return $this->_baseUrl . 'part-ymm/' . "{$year}-{$make}-{$model}-{$ymm['partname']}"  . $this->_urlExt;
                break;
        }
    }

    /**
     * @param $word
     * @return mixed
     */
    protected function _cleanUrl($word)
    {
        $word = Mage::helper('hauto/path')->filterTextToUrl($word);
        return $word;
    }

    /**
     * Generate YMM
     */
    protected function _generateYmm()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $query = "SELECT ymm.product_id as product_id, year.name as year, make.name as make, model.name as model FROM auto_combination_list as ymm
                      LEFT JOIN auto_combination_list_labels  as year ON year.option = ymm.year
                      LEFT JOIN auto_combination_list_labels  as make ON make.option = ymm.make
                      LEFT JOIN auto_combination_list_labels  as model ON model.option = ymm.model
                        ";
        $results = $readConnection->fetchAll($query);
        foreach ($results as $row){
            $this->_ymm[$row['product_id']][] = array(
                'year' => $row['year'],
                'make' =>  ucwords($row['make']),
                'model' => ucwords($row['model'])
            );
        }
    }


    /**
     * @param $product
     * @return false|string
     */
    protected function _getYmmLabel($product)
    {
        $ymm = array();
        $productId = $product->getId();
        if(array_key_exists($productId, $this->_ymm)){

            $ymm = $this->_ymm[$productId];
        }
        return $ymm;
    }

    public function usageHelp()
    {
        return "
\n
Usage:  php -f googlefeedcyrus.php -- [options] 
\n \n --deploy <argvalue> To Run the script
\n --showstatus To Show Status of exporting
\n
\n
";
    }


}

$shell = new Generate_Fitment();
$shell->run();
