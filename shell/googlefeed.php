<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/15/17
 * Time: 7:59 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    private $feedType;
    const STATUS_ATTRIBUTE_ID = 96;
    const NAME_ATTRIBUTE_ID = 71;
    const PART_NAME_ATTRIBUTE_ID = 249;
    const STATUS_ATTRIBUTE_ENABLE = 1;
    protected $_missingGTINItems = array();
    protected $_orderItemGrossAmount;

    protected $_brand = array(
      2 => 'Subaru'
    );

    public function run()
    {
        $arg = $this->getArg('generate');
        $website = $this->getArg('website');

        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);

        if($arg && $website){
            echo "START >> " . now();
            $websites = explode(',',$website);
            foreach($websites as $website){

                $this->_orderItemGrossAmount = $this->getMonthlyGrossAmount($website);
                $productIds = $this->fetchEnabledProducts($website);

                $nonVehicleFeed = array();
                $nonVehicleFeed = $this->getNonVehicleData($productIds,$website);
                array_unshift($nonVehicleFeed, $this->_getFeedColumns(array('color','size')));
                $this->_createFile($nonVehicleFeed, 'non-ymm-' . $website . '.txt');

              /*$vehicleFeed = array();
                $vehicleFeed = $this->getVehicleData($productIds,$website);
                array_unshift($vehicleFeed, $this->_getFeedColumns());
                $this->_createFile($vehicleFeed, 'vehicle-' . $website . '.txt');*/
            }

            echo "\nEND >> " . now();
            echo "\n";
        }
        else if ($arg == true) {
            echo "\n Start Generation Old >> " . now();
            $_products = $this->_getProducts();
            /** @var Varien_Db_Select $select */

            $vehicleFeed = array();
            $vehicleFeed = $this->_getProductYmmsData($_products);
            array_unshift($vehicleFeed, $this->_getFeedColumns());
            $this->_createFile($vehicleFeed, 'vehicle.txt');
            echo "\n End Generation  >> " . now();
        }
        $dryrun = $this->getArg('dry-run');
        if($dryrun === true){
            $products = $this->_getProducts();
        }
    }
    public function fetchEnabledProducts($websiteId){
        $enabledProducts = array();
        $products = $this->fetchProducts($websiteId);
        foreach($products as $product){
            if($this->isProductEnabled($product, $websiteId)){
                array_push($enabledProducts,$product);
            }
        }
        return $enabledProducts;
    }
    public function fetchProducts($websiteId){
        $reader = $this->_getReader();
        $productWebsiteTable = $this->_getResource()->getTableName('catalog/product_website');
        //Single Website Assignment Only
        $select = $reader->select()
            ->from(array('p' => $productWebsiteTable))
            ->where('p.website_id=?',$websiteId);
//            ->where('p.product_id=?', 5564);

        if(Mage::helper('shoppingfeed')->isEnableFilterProduct()) {
            $select->joinLeft('cataloginventory_stock_status', 'cataloginventory_stock_status.product_id = p.product_id', array('stock_status'));
            $select->where("`cataloginventory_stock_status`.`stock_status` = 1");
            $select->group('p.product_id');
        }

        $result = $reader->fetchCol($select,array('s.entity_id'));
        return $result;
    }
    public function isProductEnabled($productId, $websiteId){

        $reader = $this->_getReader();
        /** @var Mage_Core_Model_Website $store */
        $website = Mage::getModel('core/website')->load($websiteId);
        $defaultStoreId = $website->getDefaultStore()->getId();
        $statusAttributeValueTable = $this->_getResourceModel()->getValueTable('catalog/product','int');
        if(!Mage::app()->isSingleStoreMode()){
            $select = $reader->select()
                ->from(array('s' => $statusAttributeValueTable))
                ->where('s.entity_id = ?',$productId)
                ->where('s.attribute_id = ?', self::STATUS_ATTRIBUTE_ID)
                ->where('s.value = ?', self::STATUS_ATTRIBUTE_ENABLE)
                ->where('s.store_id = ?', $defaultStoreId);

            if(count($reader->fetchAll($select)) == 1){
                return true;
            }
        }
        //Use default value
        $defaultStoreId = 0;
        $select = $reader->select()
            ->from(array('s' => $statusAttributeValueTable))
            ->where('s.entity_id = ?',$productId)
            ->where('s.attribute_id = ?', self::STATUS_ATTRIBUTE_ID)
            ->where('s.value = ?', self::STATUS_ATTRIBUTE_ENABLE)
            ->where('s.store_id = ?', $defaultStoreId);
        return count($reader->fetchAll($select)) == 1;
    }
    public function getProductSku($productId){
        $reader = $this->_getReader();
        $productEntityTable = $this->_getResourceModel()->getTable('catalog/product');
        $select = $reader->select()
            ->from(array('e' => $productEntityTable))
            ->where('e.entity_id=?', $productId);
        $row = $reader->fetchRow($select);
        if(!is_array($row) || !array_key_exists('sku', $row))
            return null;
        return $row['sku'];
    }
    
    public function getProductName($productId, $websiteId){
        $nameAttributeValueTable = $this->_getResourceModel()->getValueTable('catalog/product','varchar');
        return $this->getAttributeValue($productId, $websiteId,self::NAME_ATTRIBUTE_ID,$nameAttributeValueTable);
    }
    public function getProductPartName($productId, $websiteId){
        $partNameAttributeValueTable = $this->_getResourceModel()->getValueTable('catalog/product','varchar');
        return $this->getAttributeValue($productId, $websiteId,self::PART_NAME_ATTRIBUTE_ID,$partNameAttributeValueTable);
    }
    public function getProductSkuUrl($product, $websiteId){

        if(!empty($product->getCustomUrlKey())){
            $sku = $product->getCustomUrlKey();
        }else{
            $sku = $product->getSku();
        }

        $website = Mage::getModel('core/website')->load($websiteId);
        $url = Mage::getStoreConfig('web/unsecure/base_url', $website->getDefaultStore());
        $useSecure = Mage::getStoreConfig('web/secure/use_in_frontend',$website->getDefaultStore());
        if($useSecure){
            $url = Mage::getStoreConfig('web/secure/base_url', $website->getDefaultStore());
        }
        $skuUrl = $url . 'sku/' . $sku . '.html';

        return $skuUrl;
    }

    /**
     * Get Month Gross Amount by Product ID
     * @param $productId
     * @return int
     */
    public function getMonthlyGrossAmountById($productId)
    {
        $grossAmount = null;
        if(isset($this->_orderItemGrossAmount[$productId])){
            $grossAmount =  $this->_orderItemGrossAmount[$productId]['monthly_gross'];
        }

        return $grossAmount;
    }

    /**
     * @return array
     */
    public function getMonthlyGrossAmount($websiteId)
    {
        $website = Mage::getModel('core/website')->load($websiteId);
        $storeArray = [];
        foreach ($website->getGroups() as $group) {
            $stores = $group->getStores();
            foreach ($stores as $store) {
                $storeArray[] =  $store->getId();
            }
        }

        $storeIds = implode(',',$storeArray);
        $timestamp    = $currentTimestamp = Mage::getModel('core/date')->timestamp(time());
        $startOfMonth = date('2015-m-01 00:00:00', $timestamp);
        $endOfMonth  = date('Y-m-t 23:59:59', $timestamp);

        $collection = Mage::getResourceModel('sales/order_item_collection')->addAttributeToSelect('product_id');
        $collection->getSelect()->join( array('orders'=> 'sales_flat_order'), 'orders.entity_id= main_table.order_id', array('orders.status'))
            ->columns('SUM(row_total) as monthly_gross,COUNT(qty_ordered) AS total_ordered')
            ->where("orders.status in ('complete','closed')")
            ->where("orders.created_at between '{$startOfMonth}' AND '{$endOfMonth}'")
            ->where("orders.store_id in ({$storeIds})")
            ->group('main_table.product_id');

        $orderItems = [];
        foreach ($collection as $item){
            $orderItems[$item->getProductId()] = array(
                'monthly_gross' => $item->getMonthlyGross(),
                'total_ordered' => $item->getTotalOrdered()
            );
        }

        return $orderItems;
    }

    /**
     * @param $amount
     * @return mixed
     */
    public function getMonthlyGrossLabel($amount)
    {

        if(!$amount) {
            $amount = 0;
        }

        $amount = floor($amount);
        $rangeArray = array(
            '0_50' => 'Lowest-Grossing',
            '51_100' => 'Low-Grossing ',
            '101_300' => 'Mid-Grossing',
            '301_499' => 'High-Grossing',
            '500_MAX' => 'Highest-Grossing',
        );

        foreach ($rangeArray as $num => $label){
            $getRange = explode('_', $num);


            if( $amount >= $getRange[0] && $amount <= $getRange[1]){
                return  $label;
            }else if($amount >= $getRange[0] && $getRange[1] == 'MAX') {
                return  $label;
            }
        }
    }

    public function getVehicleDescription($productId, $websiteId = 1){

    }

    public function getNonVehicleDescription($productId, $websiteId = 1, $brand = null){
        $description = 'Buy %s. Find OEM part # %s. Shop for genuine '.$brand.' factory original %s';
        $sku = $this->getProductSku($productId);
        $title = $this->getProductName($productId,$websiteId);
        $partNameStr = $this->getProductPartName($productId,$websiteId);
        $partName = str_replace('â€šÃ„Ã£','',preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($partNameStr)));
        return sprintf($description,$title,$sku,$partName);
    }

    public function getNonVehicleData($productIds,$websiteId){
        $result = array();
        $brand = isset($this->_brand[$websiteId]) ? $this->_brand[$websiteId] : 'Mopar';
        foreach($productIds as $productId){
            $_product = Mage::getModel('catalog/product')->load($productId);
            if($this->hasPricing($productId)){
                $productName = $this->getProductName($productId, $websiteId);
                $productSku = $this->getProductSku($productId);
                $productPartName = $this->getProductPartName($productId,$websiteId);

                $googleProductCategory = $_product->getGsfCategoryId();
                if(empty($googleProductCategory)) {
                    $this->_getGoogleAmpCategory($_product);
                }

                $result[] = array(
                    'id' => $productId,
                    'title' => $productName . '. OEM # ' . $productSku,
                    'description' => $this->getNonVehicleDescription($productId, $websiteId, $brand),
                    'link' => $this->getProductSkuUrl($_product, $websiteId),
                    'image_link' => $this->_getBaseImageUrl($_product, $websiteId),
                    'additional_image_link' => $this->_getMediaGallery($_product, $websiteId),
                    'price' => number_format($this->_getPrice($_product), 2) . ' USD',
                    'shipping' => !empty($_product->getFreeShippingProduct()) ? '0.00 USD' : '',
                    'sale_price' => $this->_getProductSP($_product),
                    'sale_price_effective_date' => '',
                    'availability' => 'in stock',
                    'shipping_weight' => number_format($_product->getWeight(), 2) . ' lb',
                    'shipping_length' => ceil($_product->getShipLength()) . ' in',
                    'shipping_height' => ceil($_product->getShipHeight()) . ' in',
                    'shipping_width' =>  ceil($_product->getShipWidth()) . ' in',
                    'brand' => 'Genuine ' . $brand,
                    'mpn' => strtolower($productSku),
                    'identifier_exists' => 'true',
                    'condition' => 'new',
                    'product_type' => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($productPartName)),
                    'google_product_category' => $googleProductCategory,
                    'custom_label_0' => $productSku,
                    'custom_label_1' => 'Non YMM',
                    'custom_label_2' => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($productPartName)),
                    'custom_label_3' => $this->getMonthlyGrossLabel($this->getMonthlyGrossAmountById($_product->getId())),
                    'color' => $_product->getGsfColor(),
                    'size' => $_product->getAttributeText('size')

                );
            }
        }
        return $result;
    }

    public function getVehicleData($productIds, $websiteId){
        $result = array();
        foreach($productIds as $productId){
            $_product = Mage::getModel('catalog/product')->load($productId);
            if($this->hasPricing($productId)){
                $productSku = $this->getProductSku($productId);
                $productPartName = $this->getProductPartName($productId,$websiteId);
                $_mixes = Mage::getModel('hautopart/mix')->getCollection()
                    ->addFieldToFilter('product_id', $productId);
                /** @var Homebase_Autopart_Model_Mix $_mix */
                foreach($_mixes as $_mix){
                    /** @var Homebase_Auto_Model_Resource_Index_Combination $_routes */
                    $_routes = Mage::getResourceModel('hauto/index_combination');
                    $fitment = serialize($_mix->toArray(array('year','make','model')));
                    $route = $_routes->fetchRoute($fitment);
                    $prodname = ucwords(implode(' ',explode('-',$route)));
                    $idString = $productSku. str_replace('-','',$route);
                    if(strlen($idString) > 45){
                        echo $productSku . ' >> ' . $idString . "\n";
                        $idString = $productSku;
                    }
                    $row = array(
                        'id'    => $idString,
                        'title' => sprintf('%s %s. OEM # %s',$prodname,preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($productPartName)), $productSku),
                        'description' => $this->_formatVehicleDescription($_product,$prodname),
                        'link'  => $this->_getYmmsUrl($_product,$route, $websiteId),
                        'image_link' => $this->_formatImagePaths($this->_getBaseImageUrl($_product, $websiteId),$_product,str_replace('-','',$route)),
                        'additional_image_link' => $this->_formatImagePaths($this->_getMediaGallery($_product, $websiteId),$_product,str_replace('-','',$route)),
                        'price' => number_format($this->_getPrice($_product), 2) . ' USD',
                        'sale_price'                => $this->_getProductSP($_product),
                        'sale_price_effective_date' => '',
                        'availabilit
                        y'              => 'in stock',
                        'shipping_weight'           => number_format($_product->getWeight(),2) . ' lb',
                        'shipping_length' => ceil($_product->getShipLength()) . ' in',
                        'shipping_height' => ceil($_product->getShipHeight()) . ' in',
                        'shipping_width' =>  ceil($_product->getShipWidth()) . ' in',
                        'brand'                     => ucwords($this->_getVehicleMake($_mix->getMake())),
                        'mpn'                       => strtolower($productSku),
                        'identifier_exists' => 'true',
                        'condition'                 => 'new',
                        'product_type'              => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($productPartName)),
                        'google_product_category'   => $this->_getGoogleAmpCategory($_product),
                        'custom_label_0'            => $productSku,
                        'custom_label_1'            => 'Vehicle',
                        'custom_label_2'            => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($productPartName)),
                        'custom_label_3' => $this->getMonthlyGrossAmountById($_product->getId())
                    );
                    if(!$this->_inArray($productSku . str_replace('-','',$route),$result)){
                        $result[] = $row;
                    }
                }
            }
        }
        return $result;
    }


    protected function getAttributeValue($productId, $websiteId, $attributeId, $table){
        $reader = $this->_getReader();
        /** @var Mage_Core_Model_Website $store */
        $website = Mage::getModel('core/website')->load($websiteId);
        $defaultStoreId = $website->getDefaultStore()->getId();
        if(!Mage::app()->isSingleStoreMode()){
            $select = $reader->select()
                ->from(array('s' => $table))
                ->where('s.entity_id = ?',$productId)
                ->where('s.attribute_id = ?', $attributeId)
                ->where('s.store_id = ?', $defaultStoreId);
            $row = $reader->fetchRow($select);

            if(is_array($row) && array_key_exists('value', $row)){
                return $row['value'];
            }
        }
        //Use default value
        $defaultStoreId = 0;
        $select = $reader->select()
            ->from(array('s' => $table))
            ->where('s.entity_id = ?',$productId)
            ->where('s.attribute_id = ?', $attributeId)
            ->where('s.store_id = ?', $defaultStoreId);
        $row = $reader->fetchRow($select);
        if(is_array($row) && array_key_exists('value', $row)){
            return $row['value'];
        }
        return null;
    }
    /**
     * @return Magento_Db_Adapter_Pdo_Mysql
     */
    protected function _getReader(){
        $resource = $this->_getResource();
        return $resource->getConnection('core_read');
    }

    /**
     * @return Mage_Core_Model_Resource_Resource
     */
    protected function _getResourceModel(){
        return Mage::getResourceModel('core/resource');
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource(){
        return Mage::getSingleton('core/resource');
    }
    /**
     * @param $product Homebase_Autopart_Model_Product
     * @return mixed
     */
    public function _getPrice($product){

        /** @var Mage_Core_Model_Resource#12 $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readConnection */
        $readConnection = $resource->getConnection('core_read');

        $select = $readConnection->select()
            ->from($resource->getTableName('aitcbp/product_price_index'))
            ->where('website_id=?',1)
            ->where('product_id=?',$product->getId())
            ->limit(1);
        $result = $readConnection->fetchRow($select->__toString());
        if(!$result){
            return $product->getPrice();
        }
        return $result['price'];
    }
    private function _getGoogleAmpCategory($_product){
        $category = $_product->getAttributeText('auto_type');
        if(is_array($category)){
            $category = array_shift($category);
        }
        /**
         *$map = array(
         *    'aerodynamic parts' => 8227,
         *    'exterior accessories' => 913,
         *    'audio & electronics' => 8526,
         *    'audio / video accessories' => 8526,
         *    'bed accessories'   => 8378,
         *    'cargo & towing accessories' => 8237,
         *    'carpet mats' => 8232,
         *    'door handles' => 8233,
         *    'dvd & electronic accessories'  => 8526,
         *    'floor mats' => 8232,
         *    'interior accessories' => 8233,
         *    'lift kits' => 2935,
         *    'mopar performance parts' => 2820,
         *    'police pursuit electrical equipment' => 8526,
         *    'police pursuit exterior equipment' => 8526,
         *    'police pursuit interior equipment' => 8526,
         *    'ram promaster accessories' => 8233,
         *    'remote start & electronics accessories' => 2699,
         *    'rubber all weather mats' => 8232,
         *    'side steps & rock rails' => 362737,
         *    'sories/viper tune-up parts' => 2820,
         *    'tire covers' => 8310,
         *    'tops' => 7031,
         *    'doors & window kits'=> 7031,
         *    'tune-up parts' => 2820,
         *    'wheels & related accessories' => 6090
         * );
         */
        $map = array(
            'aerodynamic parts' => 899,
            'cargo, towing & racking accessories' => 6454,
            'carpet mats' => 8232,
            'door handles' => 8227,
            'doors & window kits' => 2534,
            'exterior accessories' => 2495,
            'interior accessories' => 2495,
            'led lighting' => 3318,
            'lift kits' => 2935,
            'mopar performance parts' => 899,
            'off road accessories' => 899,
            'off road bumpers' => 8227,
            'performance parts' => 899,
            'police pursuit electrical equipment' => 8301,
            'police pursuit exterior equipment' => 8301,
            'police pursuit interior equipment' => 8301,
            'recon led lighting' => 3318,
            'remote start, electronics & audio accessories' => 2699,
            'remote start/ electronics & audio accessories' => 2699,
            'rubber all weather mats' => 8232,
            'side steps, running boards & rock rails' => 2495,
            'subaru gear' => 2495,
            'subaru gear & accessories' => 2495,
            'subaru performance parts' => 899,
            'official mopar gear & apparel' => 2495,
            'tire covers' => 2989,
            'tonneau covers & bed accessories' => 8308,
            'top selling parts' => 899,
            'tops' => 2494,
            'tune-up & maintenance parts' => 899,
            'wheels & related accessories' => 2932,
            'wheels & related accessories/exterior accessories' => 2932,
            'appearance & protection' => 8227,
            'body kits & related items' =>	8227,
            'roush performance body kits and exterior parts' =>	8227,
            'ford performance appearance & dress-up' =>	8227,
            'decals, emblems & stripes' =>	2722,
            'grilles' => 8227,
            'ford performance brake parts' =>	899,
            'ford performance cold air intakes & filters' =>	899,
            'ford performance driveline, suspension & chassis' =>	2935,
            'ford performance engine & power upgrades' =>	2820,
            'ford performance engine blocks & related parts' =>	2820,
            'ford performance wheels & related parts' =>	3020,
            'ford performance heads & valvetrain parts' =>	2820,
            'ford performance ignition, fuel systems and electrical' =>	2788,
            'ford performance oiling components' =>	2788,
            'ford performance superchargers' =>	2788,
            'ford performance cooling systems & related parts' =>	2788,
            'ford performance crate engines' =>	8137,
            'roush performance wheels & related parts' =>	2556,
            'ford performance exhaust systems' =>	908,
            'ford performance engine parts' =>	2820,
            'roush performance interior add-ons and misc.' =>	2495,
            'roush performance ignition, fuel systems and electrical' =>	913,
            'roush performance brakes and related parts' =>	913,
            'roush performance decals, badges and stripe kits' =>	913,
            'roush performance driveline components' => 5613,
            'roush performance engine components' =>	2820,
            'roush performance engine power upgrades' =>	2820,
            'roush performance exhaust systems' =>	908,
            'roush performance filters and cold air intakes' =>	913,
            'roush performance spoilers' =>	899,
            'spoilers' =>	899,
            'roush performance superchargers' =>	899,
            'roush performance suspension parts' =>	899,
            'seat covers' =>	913,
            'ford performance off-road accessories' =>	913,
            'covers & miscellaneous' => 913,
            'ford apparel & gear' => 2495,
        );
        $key =strtolower($category);
        if(array_key_exists($key,$map)){
            return $map[$key];
        }
        return '';
    }
    private function _getProductYmmsData($products){
        $result = array();
        $ctr = 0;
        /** @var Mage_Catalog_Model_Product $_product */
        foreach($products as $_product){
            if($this->hasPricing($_product->getId())){
                $_mixes = Mage::getModel('hautopart/mix')->getCollection()
                    ->addFieldToFilter('product_id', $_product->getId());
                /** @var Homebase_Autopart_Model_Mix $_mix */
                foreach($_mixes as $_mix){
                    /** @var Homebase_Auto_Model_Resource_Index_Combination $_routes */
                    $_routes = Mage::getResourceModel('hauto/index_combination');
                    $fitment = serialize($_mix->toArray(array('year','make','model')));
                    $route = $_routes->fetchRoute($fitment);
                    $prodname = ucwords(implode(' ',explode('-',$route)));
                    $row = array(
                        'id'    => $_product->getSku() . str_replace('-','',$route),
                        'title' => sprintf('%s %s. OEM # %s',$prodname,preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())), $_product->getSku()),
                        'description' => $this->_formatVehicleDescription($_product,$prodname),
                        'link'  => $this->_getYmmsUrl($_product,$route),
                        'image_link' => $this->_formatImagePaths($this->_getBaseImageUrl($_product),$_product,str_replace('-','',$route)),
                        'additional_image_link' => $this->_formatImagePaths($this->_getMediaGallery($_product),$_product,str_replace('-','',$route)),
                        'price' => number_format($this->_getPrice($_product), 2) . ' USD',
                        'sale_price'                => $this->_getProductSP($_product),
                        'sale_price_effective_date' => '',
                        'availabilit
                        y'              => 'in stock',
                        'shipping_weight'           => number_format($_product->getWeight(),2) . ' lb',
                        'shipping_length' => ceil($_product->getShipLength()) . ' in',
                        'shipping_height' => ceil($_product->getShipHeight()) . ' in',
                        'shipping_width' =>  ceil($_product->getShipWidth()) . ' in',
                        'brand'                     => ucwords($this->_getVehicleMake($_mix->getMake())),
                        'mpn'                       => strtolower($_product->getSku()),
                        'identifier_exists' => 'true',
                        'condition'                 => 'new',
                        'product_type'              => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())),
                        'google_product_category'   => $this->_getGoogleAmpCategory($_product),
                        'custom_label_0'            => $_product->getSku(),
                        'custom_label_1'            => 'Vehicle',
                        'custom_label_2'            => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())),
                        'custom_label_3' => $this->getMonthlyGrossAmountById($_product->getId())
                    );
                    if(!$this->_inArray($_product->getSku() . str_replace('-','',$route),$result)){
                        $result[] = $row;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $product Mage_Catalog_Model_Product
     * @return string|void
     */
    public function _getProductSP($product){
        if(!$product->hasData('special_price')){
            return;
        }
        if($product->getSpecialPrice() > 0){
            return number_format($product->getSpecialPrice(),2) . ' USD';
        }
        return;
    }

    private function _inArray($key,$row){
        foreach($row as $item){
            if($item['id'] === $key){
                return true;
            }
        }
        return false;
    }
    private function _getVehicleMake($makeCode){
        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');
        return $_helper->getOptionText('make',$makeCode);
    }
    private function _getYmmsUrl($product, $ymmroute, $websiteId = 1){
        $website = Mage::getModel('core/website')->load($websiteId);
        $url = Mage::getStoreConfig('web/unsecure/base_url', $website->getDefaultStore());
        $useSecure = Mage::getStoreConfig('web/secure/use_in_frontend',$website->getDefaultStore());
        if($useSecure){
            $url = Mage::getStoreConfig('web/secure/base_url', $website->getDefaultStore());
        }

        if(!empty($product->getCustomUrlKey())){
            $sku = $product->getCustomUrlKey();
        }else {
            $sku = $product->getSku();
        }

        return $url . 'sku-ymm/' . $ymmroute . '/' . $sku . '.html';
    }
    private function _getProductData($products){
        $result = array();
        /** @var Homebase_Autopart_Model_Product $_product */
        foreach($products as $_product) {
            if($this->hasPricing($_product->getId())){
                $result[] = array(
                    'id' => $_product->getSku(),
                    'title' => $_product->getName() . '. OEM # ' . $_product->getSku(),
                    'description' => $this->_getNonVehicleFitProductDescription($_product),
                    'link' => $this->_formatProductUrl($_product),
                    'image_link' => $this->_getBaseImageUrl($_product),
                    'additional_image_link' => $this->_getMediaGallery($_product),
                    'price' => number_format($this->_getPrice($_product), 2) . ' USD',
                    'sale_price' => $this->_getProductSP($_product),
                    'sale_price_effective_date' => '',
                    'availability' => 'in stock',
                    'shipping_weight' => number_format($_product->getWeight(), 2) . ' lb',
                    'shipping_length' => ceil($_product->getShipLength()) . ' in',
                    'shipping_height' => ceil($_product->getShipHeight()) . ' in',
                    'shipping_width' =>  ceil($_product->getShipWidth()) . ' in',
                    'brand' => 'Genuine Mopar',
                    'mpn' => strtolower($_product->getSku()),
                    'identifier_exists' => 'true',
                    'condition' => 'new',
                    'product_type' => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())),
                    'google_product_category' => $this->_getGoogleAmpCategory($_product),
                    'custom_label_0' => $_product->getSku(),
                    'custom_label_1' => 'Non Vehicle',
                    'custom_label_2' => preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())),
                    'custom_label_3' => $this->getMonthlyGrossAmountById($_product->getId())
                );
            }
        }
        return $result;
    }
    private function _formatImagePaths($path, $_product, $ymm){
        $paths  = explode(',',$path);
        $newpaths = array();
        if(is_array($paths) && !empty($paths)){
            foreach($paths as $url){
                if(trim($url)!==''){
                    $newpaths[] = $url. '?'. $ymm . $_product->getSku();
                }
            }
            return implode(',',$newpaths);
        }
    }
    private function _getMediaGallery($product, $websiteId = null){
        $_product = Mage::getModel('catalog/product')->load($product->getId());
        $galleryImages = array();
        $website = Mage::getModel('core/website')->load($websiteId);
        $websiteUrl = Mage::getStoreConfig('web/unsecure/base_url', $website->getDefaultStore());
        $useSecure = Mage::getStoreConfig('web/secure/use_in_frontend',$website->getDefaultStore());
        if($useSecure){
            $websiteUrl = Mage::getStoreConfig('web/secure/base_url', $website->getDefaultStore());
        }

        foreach ($_product->getMediaGalleryImages() as $image) {
            $file = $image->getFile();
            $fullpath = $websiteUrl . 'media/catalog/product' . $file;
            array_push($galleryImages,$fullpath);
//            $galleryImages[] = $image->getUrl();
        }
        return implode(',', $galleryImages);
    }
    private function _getBaseImageUrl($product, $websiteId = 1){
        $website = Mage::getModel('core/website')->load($websiteId);
        $url = Mage::getStoreConfig('web/unsecure/base_url', $website->getDefaultStore());
        $useSecure = Mage::getStoreConfig('web/secure/use_in_frontend',$website->getDefaultStore());
        if($useSecure){
            $url = Mage::getStoreConfig('web/secure/base_url', $website->getDefaultStore());
        }
        return $url . 'media/catalog/product' . $product->getImage();
    }
    private function _formatProductUrl($product){
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'sku/' . $product->getSku() . '.html';
        return $url;
    }
    private function _formatProductName($product){
        $name = $product->getPartName() . ' ' . $product->getName();
        $name = str_ireplace(' with ', ' w/ ', $name);
        return trim($name);
    }
    private function _getNonVehicleFitProductDescription($_product){
        $description = 'Buy %s. Find OEM part # %s. Shop for genuine Mopar factory original %s';
        $sku = $_product->getSku();
        $title = $_product->getName();
        $partName = str_replace('Ã¢â‚¬Å¡Ãƒâ€žÃƒÂ£','',preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())));
        return sprintf($description,$title,$sku,$partName);
    }

    private function _formatVehicleDescription($_product,$fitment){
        $description = 'Genuine %s %s. OEM # %s. Shop for Original Factory %s.';
        $partName =str_replace('Ã¢â‚¬Å¡Ãƒâ€žÃƒÂ£','',preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($_product->getPartName())));
        $sku = $_product->getSku();
        $name = $_product->getName();
        return sprintf($description,$fitment,$partName,$sku,$name);
    }
    private function _getFeedColumns($additional = array()){
        $columns = array(
            'id',
            'title',
            'description',
            'link',
            'image_link',
            'additional_image_link',
            'price',
            'shipping',
            'sale_price',
            'sale_price_effective_date',
            'availability',
            'shipping_weight',
            'shipping_length',
            'shipping_height',
            'shipping_width',
            'brand',
            'mpn',
            'identifier_exists',
            'condition',
            'product_type',
            'google_product_category',
            'custom_label_0',
            'custom_label_1',
            'custom_label_2',
            'custom_label_3'
        );

        if(!empty($additional)){
            $columns = array_merge($columns, $additional);
        }

        return $columns;
    }
    public function _createFile($data,$filename){
        try{
            echo     $filepath = Mage::getBaseDir('base') . DS . 'feed' . DS . $filename;
            /** @var Varien_File_Csv $_csv */
            $_csv = new Varien_File_Csv();
            $_csv->setDelimiter("\t");
            $_csv->saveData($filepath,$data);
        }catch(Exception $e){
            echo $e->getMessage() . PHP_EOL;
        }

    }
    private function _getProducts($websiteId = 1){
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('auto_type')
            ->addAttributeToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addWebsiteFilter($websiteId);


        if(Mage::helper('shoppingfeed')->isEnableFilterProduct()) {
            $collection->getSelect()->join('cataloginventory_stock_status', 'cataloginventory_stock_status.product_id = e.entity_id', array('stock_status'));
            $collection->getSelect()->where("`cataloginventory_stock_status`.`stock_status` = 1")->distinct('e.entity_id');
        }

        return $collection;
    }
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f googlefeed.php -- [options]
  --generate <feed_type>        Generate Google Feed
  help                          This help

  <indexer>     Comma separated indexer codes or value "all" for all indexers

USAGE;
    }

    public function hasPricing($productId){

        $hasPricing = false;

        /** @var Mage_Core_Model_Resource $connection */
        $connection = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $connection->getConnection('read');
        /** @var Mage_Catalog_Model_Resource_Product_Indexer_Eav  $resource */
        $resource = Mage::getResourceSingleton('catalog/product_indexer_eav');
        $decimalTable = $resource->getValueTable('catalog/product','decimal');

        $priceAttributes = array();
        $priceAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product','price');
        $specialPriceAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product','special_price');
        $costAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product','cost');

        array_push($priceAttributes, $priceAttribute->getId());
        array_push($priceAttributes, $specialPriceAttribute->getId());
        array_push($priceAttributes, $costAttribute->getId());

        $select = $reader->select()
            ->from(array('t' => $decimalTable),'value')
            ->where('t.entity_id=?', $productId)
            ->where('t.attribute_id IN(?)', $priceAttributes );
        $result = $select->query();
        $values = $result->fetchAll();
        foreach($values as $value){
            if(is_array($value)){
                $val = array_pop($value);
                if(!is_null($val)){
                    $hasPricing =true;
                    break;
                }
            }
        }
        return $hasPricing;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();
