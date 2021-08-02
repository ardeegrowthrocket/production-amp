<?php


require_once 'abstract.php';

class Generate_Facebookfeed extends Mage_Shell_Abstract
{

    protected $_feedTitle = "fb_feed";
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
            'title',
            'description',
            'product_type',
            'link',
            'image_link',
            'condition',
            'availability',
            'price',
            'sale_price',
            'sale_price_effective_date',
            'brand',
            'custom_label_0',
            'custom_label_1',
            'custom_label_2',
            'custom_label_3',
            'custom_label_4',
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
                ->addAttributeToSelect(array('sku','name','image','small_image','part_name','custom_url_key','description','auto_type','featured','price','special_price','special_to_date','special_from_date'))
                ->setStoreId($storeId)
                ->addAttributeToFilter('status','1')
                ->addMinimalPrice()
                ->addWebsiteFilter()
                ->addFinalPrice()
                ->addAttributeToSort('entity_id', 'desc');

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        $this->_currentTimeStamp = Mage::getModel('core/date')->timestamp(time());
        $this->_baseUrl = Mage::getBaseUrl();
        $brand = str_replace(' ','', $this->_currentWebsite['name']);
        foreach ($collection as $product){
            
            echo "Execution on : " . $product->getSku() . PHP_EOL;
            $fitments = $this->_getYmmLabel($product);
            $productArray = array(
                'id' => "",
                'title' => $product->getName(),
                'description'   => $this->_getDescription($product),
                'product_type'  => $this->_getProductType($product),
                'link'  => $this->_getProductUrl($product),
                'image_link'    => $this->_getProductImage($product),
                'condition' => 'new',
                'availability'  => 'in stock',
                'price' => $this->_getPrice($product),
                'sale_price'    => $this->_getSalesPrice($product),
                'sale_price_effective_date' => $this->_getSalePriceEffectiveDate($product),
                'brand' => $brand,
                'custom_label_0'    => "",
                'custom_label_1'    => "",
                'custom_label_2'    => "",
                'custom_label_3'    => $this->_getCategory($product),
                'custom_label_4'    => $this->_isFreeShipping($product)
            );

            foreach ($fitments as  $ymm){
                $year   = $ymm['year'];
                $make   = $ymm['make'];
                $model  = $ymm['model'];
                $itemId = $product->getSku() . str_replace(' ','', $year . $make . $model);

                $productArray['id'] = $itemId;
                $productArray['custom_label_0'] = $year;
                $productArray['custom_label_1'] = $make;
                $productArray['custom_label_2'] = $model;

                $productData[$itemId] = $productArray;
            }
        }

        $time2 = microtime(true);
        $totalTime = date('H:i:s', $time2 - $time1);
        echo "Script execution time on {$this->_currentWebsite['name']}: " . $totalTime . PHP_EOL;

        return $productData;
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
                'make' => $row['make'],
                'model' => $row['model']
            );
        }
    }

    /**
     * @param $product
     * @return mixed
     */
    protected function _getId($product)
    {
        return $product->getSku();
    }

    /**
     * @param $product
     * @return string
     */
    protected function _getDescription($product)
    {
        return htmlentities($product->getDescription());
    }

    /**
     * @param $product
     * @return string
     */
    protected function _getProductType($product)
    {
        return !empty($product->getPartName()) ? $product->getPartName() : '';
    }

    /**
     * @param $product
     * @return string
     */
    protected function _getPrice($product)
    {
        $price =  $this->_checkIFvalidSpecialPrice($product) ? $product->getPrice() : $product->getFinalPrice() ;
        $price = $this->_formatPrice($price);
        $price .= ' USD';

        return $price;
    }

    /**
     * @param $product
     * @return string
     */
    protected function _getSalesPrice($product)
    {
        $specialPrice = $this->_checkIFvalidSpecialPrice($product) ? $product->getSpecialPrice() : '';
        if(!empty($specialPrice)){
            $specialPrice =  $this->_formatPrice($specialPrice);
            $specialPrice .= ' USD';
        }

        return $specialPrice;
    }

    /**
     * @param $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        return number_format((float)$price, 2, '.', '');
    }

    /**
     * @param $product
     * @return mixed
     */
    protected function _getSalePriceEffectiveDate($product)
    {
        $specialToday =  $product->getSpecialToDate();
        if(!empty($specialToday) && $this->_checkIFvalidSpecialPrice($product)){
            $specialToday = date(DateTime::ISO8601, strtotime($specialToday));
        }else{
            $specialToday = '';
        }
        return $specialToday;
    }

    /**
     * @param $product
     * @return bool
     */
    protected function _checkIFvalidSpecialPrice($product)
    {
        $isValidSpecialPrice = false;
        $specialprice = $product->getSpecialPrice();
        $specialPriceToDate =  $product->getSpecialToDate();
        $specialPriceFromDate = $product->getSpecialFromDate();
        if ($specialprice && ($product->getPrice() > $product->getFinalPrice)){
            if($this->_currentTimeStamp >= strtotime( $specialPriceFromDate) && $this->_currentTimeStamp <= strtotime($specialPriceToDate) ||
                $this->_currentTimeStamp >= strtotime( $specialPriceFromDate) && is_null($specialPriceToDate)) {

                $isValidSpecialPrice = true;
            }
        }

        return $isValidSpecialPrice;
    }

    /**
     * @param $product
     * @return mixed
     */
    protected function _getProductUrl($product)
    {
        $customUrlKey = $product->getCustomUrlKey();
        if(!empty($customUrlKey)){
            $sku  = $customUrlKey;
        }else{
            $sku = $product->getSku();
        }

      return  $this->_hautoHelper->getSkuPath(strtolower($sku), $this->_baseUrl);
    }

    /**
     * @param $product
     * @return mixed
     */
    protected function _getProductImage($product)
    {
        $imageType = 'image';
        if($this->_isFlatProductTableEnable){
            $imageType = 'small_image';
        }
        return  (string) Mage::helper('catalog/image')->init($product, $imageType)->constrainOnly(false)
            ->keepAspectRatio(true)
            ->keepFrame(true)
            ->resize(600);
    }

    /**
     * @param $image
     * @return false|int
     */
    protected function _isValidImageFormat($image)
    {
        if(empty($image['mime'])){
            return false;
        }
        return preg_match('/(\.jpg|\.png|\.gif)$/i', $image);
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

    /**
     * @param $product
     * @return mixed
     */
    protected function _getCategory($product)
    {
        return $product->getAttributeText('auto_type');
    }

    /**
     * @param $product
     * @return int
     */
    protected function _isFreeShipping($product)
    {
        return !empty($product->getFeatured()) ? 1 : 0;
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

$shell = new Generate_Facebookfeed();
$shell->run();
