<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/02/2018
 * Time: 9:17 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    const NAME_ATTRIBUTE_ID = 71;

    const SPECIAL_PRICE_ATTRIBUTE_ID = 76;

    const GENERAL_CUSTOMER_GROUP_ID =1;

    protected $_orderItemGrossAmount = array();

    /** @var Homebase_Auto_Helper_Path $optionHelper */
    private $optionHelper;

    protected $_websitesUrl = array();

    protected $_customCategory = array();

    protected $_emailRecipient = array('engineering@growth-rocket.com','anne@growth-rocket.com');

    protected $_websiteLabel = array(3 => 'Jeep', 4 => 'Ram');

    protected $_websiteCodes = array();

    protected $_mediaUrl;

    protected $_storeIds;

    protected $_allowedWebsite = array(
        2 => 'vehicle-2.txt',
        3 => 'jau.txt',
        4 => 'rau.txt',
        5 => 'lfp.txt',
        1 => 'vehicle.txt',
        6 => 'mgp.txt',
        7 => 'sop.txt',
        8 => 'mop.txt',
        9 => 'mogp.txt'
    );

    protected $_websiteName = array();

    protected $_websiteBenchmark = array();

    public function _construct()
    {
        $this->optionHelper = Mage::helper('hauto/path');
        $this->_customCategory = $this->_getCustomerCategory();
    }

    protected function _getStore($website)
    {
        foreach ($website->getGroups() as $group){
            $stores = $group->getStores();
            foreach ($stores as $store) {
                if($store->getName() == 'German'){
                    continue;
                }
                $this->_storeIds[$website->getWebsiteId()] = $store->getId();
            }
        }

        return $this->_storeIds;
    }

    public function run()
    {

        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);
        /** @var Mage_Core_Model_Resource_Config $eavConn */
        $eavConn = Mage::getResourceModel('core/config');
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $conn->getConnection('core_read');

        $allowedWebsiteArray = array();

        $website = $this->getArg('website');
        if($this->getArg('email')){
            $this->_emailRecipient = $this->getArg('email');
        }

        if($website){
            $websiteModel = Mage::getModel('core/website')->load($website);
            $allowedWebsiteArray[$website] = $this->_allowedWebsite[$website];
            $this->_websiteName[$website] = $websiteModel->getName();
            $this->_websiteCodes[$website] = $websiteModel->getCode();

            $this->_getStore($websiteModel);
 
        }else{
            foreach (Mage::app()->getWebsites() as $website) {
                $websiteId = $website->getWebsiteId();
                if(array_key_exists($websiteId, $this->_allowedWebsite)) {
                    $allowedWebsiteArray[$websiteId] = $this->_allowedWebsite[$websiteId];
                    $this->_websiteName[$websiteId] = $website->getName();
                    $this->_websiteCodes[$websiteId] = $website->getCode();
                }

                $this->_getStore($website);
            }
        }

        $encrypted_data = md5('feed-secure-download');
        $this->_createAdditionalImagesTempTable();
        $catalogWebTable = $conn->getTableName('catalog/product_website');
        $catalogProductTable = $eavConn->getTable('catalog/product');
        $statusAttrTable = $eavConn->getValueTable('catalog/product','int');
        $decimalAttrTable = $eavConn->getValueTable('catalog/product','decimal');
        $varAttrTable = $eavConn->getValueTable('catalog/product','varchar');
        $fitmentTable = $conn->getTableName('hautopart/combination_list');
        $optionValue = $conn->getTableName('eav/attribute_option_value');
        $flatOrderItemTable = $conn->getTableName('sales_flat_order_item');

        $partName = $this->_getAttributeId('part_name');
        $customUrlKey = $this->_getAttributeId('custom_url_key');
        $shipLength = $this->_getAttributeId('ship_length');
        $shipHeight = $this->_getAttributeId('ship_height');
        $shipWidth = $this->_getAttributeId('ship_width');
        $productWeight = $this->_getAttributeId('weight');
        $productStatus = $this->_getAttributeId('status');
        $freeShipping = $this->_getAttributeId('free_shipping_product');
        $isOversized = $this->_getAttributeId('is_oversized');
        $image = $this->_getAttributeId('image');
        $sHQDimension = $this->_getAttributeId('shipperhq_dim_group');


        foreach ($allowedWebsiteArray as $website => $filename) {

            $time1 = microtime(true);
            $websiteName = $this->_websiteName[$website];
            $totalRows = 0;

            echo "Run for website: {$websiteName}" . PHP_EOL;

            $this->_mediaUrl = Mage::getStoreConfig('web/secure/base_media_url', $this->_storeIds[$website]);
            $this->_createdimensionIndex($website,$decimalAttrTable, $shipLength, $shipHeight, $shipWidth, $productWeight);
            $this->_createCustomAttributeTable($website, $varAttrTable, $customUrlKey, $partName, $image, $sHQDimension,$freeShipping, $isOversized);
            $this->_createYmmTable($website, $fitmentTable, $optionValue);
            $this->_createPriceIndex($website);
            $this->_createNameIndex($website);
            $this->_createGoogleCategory($website);
            $this->_salesGrossData($website);

            $pricingtable = sprintf('feed_price_index_%s',$website);
            $nameTable = sprintf('feed_name_index_%s', $website);
            $categorytable = sprintf('feed_category_%s',$website);
            $dimensionTable = sprintf('feed_dimension_%s',$website);
            $customAttributeTable = sprintf('feed_custom_attribute_%s',$website);
            $ymmTable = sprintf('feed_ymm_%s',$website);
            $grossSales = sprintf('feed_gross_sales_%s',$website);

            $query = $reader->select()->from(array('w' => $catalogWebTable))
                ->join(array('entity' => $catalogProductTable),'entity.entity_id=w.product_id',array('sku'))
                ->join(array('name' => $nameTable),'name.entity_id=w.product_id',array('store_name' => 'store_value','default_name' => 'default_value','store_id'))
                ->join(array('customAttribute' => $customAttributeTable),'customAttribute.product_id=w.product_id',array('part_name' => 'part_name', 'custom_url_key' => 'custom_url_key', 'image' => 'image','shipperhq_dim_group' => 'shipperhq_dim_group', 'free_shipping_product' =>
                'free_shipping_product', 'is_oversized' => 'is_oversized'))
                ->join(array('s' => $statusAttrTable),'s.entity_id=w.product_id',array('status' => 'value'))
                ->join(array('f' => $ymmTable),'f.product_id=w.product_id',array('year' => 'year','make' => 'make','model' => 'model','year_label' => 'year_label','make_label' => 'make_label','model_label' => 'model_label'))
                ->joinLeft(array('dimension' => $dimensionTable),'dimension.product_id = entity.entity_id',array('ship_length' => 'ship_length', 'ship_width' => 'ship_width','ship_height' => 'ship_height', 'weight' => 'weight'))
                ->join(array('additional' => 'feed_additional_images'),'additional.product_id = entity.entity_id', array('additional_image_link' => 'additional_images'))
                ->joinLeft(array('pricing' => $pricingtable),'pricing.entity_id = entity.entity_id',array('final_price','price','special_price','cbprice'))
                ->joinLeft(array('google' => $categorytable),'entity.entity_id = google.product_id',array('category_id'=> 'category'))
                ->joinLeft(array('sales' => $grossSales),'w.product_id = sales.product_id' ,array('revenue'=> 'revenue'))
                ->where('s.attribute_id=?', $productStatus)
                ->where('s.value=?',1)
                ->where('w.website_id=?', $website)
                ->group(array('entity.entity_id','year','make','model'))
                ->query();

            $results = $query->fetchAll();

            $records = array();
            $baseUrl = $this->getWebsiteBaseUrl($website);
            $websiteLabel = array_key_exists($website, $this->_websiteLabel) ? $this->_websiteLabel[$website] : '';

            foreach($results as $result){
                $ymm = $this->_segment($result,array('sku','year_label','make_label','model_label','custom_url_key'));
                $title = $this->_segment($result,array('year_label','make_label','model_label','part_name','sku'));
                $description = $this->_segment($result,array('year_label','make_label','model_label','part_name','sku','store_name','default_name'));
                $imageLink = array_merge($ymm,$this->_segment($result,array('image')));
                $additional = array_merge($ymm, $this->_segment($result,array('additional_image_link')));
                $pricing = $this->_segment($result,array('final_price','price','special_price', 'cbprice'));
                $specialpricing = $this->_segment($result, array('final_price','special_price'));
                $customCategoryLabel = $this->_getCategoryLabelById($result['category_id']);

                if(!empty($result['shipperhq_dim_group'])){
                    continue;
                }

                $shippingLabel = "";
                if(!empty($result['free_shipping_product'])){
                    $shippingLabel = "Free Shipping";
                }elseif(!empty($result['is_oversized'])){
                    $shippingLabel = "oversized";
                }

                $record = array(
                    'id' => $this->getId($ymm),
                    'title' => $this->getTitle($title, $websiteLabel),
                    'description' => $this->getDescription($description),
                    'link' => $this->getYmmsLink($ymm,$baseUrl),
                    'image_link' => $this->getImageLink($imageLink,$baseUrl),
                    'additional_image_link' => $this->fetchAdditionalImages($additional,$baseUrl),
                    'price' => $this->getPrice($pricing),
                    'shipping' => !empty($result['free_shipping_product']) ? '0.00 USD' : '',
                    'sale_price' => $this->getSpecialPrice($specialpricing),
                    'sale_price_effective_date' => '',
                    'availability' => 'in stock',
                    'shipping_weight' => number_format($result['weight'],2) . ' lb',
                    'shipping_length' => ceil($result['ship_length']) . ' in',
                    'shipping_height' => ceil($result['ship_height']) . ' in',
                    'shipping_width' =>  ceil($result['ship_width']) . ' in',
                    'brand' => $result['make_label'],
                    'mpn' => $result['sku'],
                    'identifier_exists' => 'true',
                    'condition' => 'new',
                    'product_type' => preg_replace('/[^a-zA-Z0-9\s]/','',strip_tags($result['part_name'])),
                    'google_product_category' => $this->getCategory($customCategoryLabel),
                    'shipping_label' => $shippingLabel,
                    'custom_label_0' => $result['sku'],
                    'custom_label_1' => 'Vehicle',
                    'custom_label_2' => preg_replace('/[^a-zA-Z0-9\s]/','',strip_tags($result['part_name'])),
                    'custom_label_3' => $this->_grossSellingLabel($result['revenue'])
                );
                $totalRows = $totalRows + 1;
                array_push($records,$record);
            }
            array_unshift($records,$this->_getFeedColumns());

            $filepath = Mage::getBaseDir('base') . DS . 'feed' . DS . $filename;
            $_csv = new Varien_File_Csv();
            $_csv->setDelimiter("\t");
            $_csv->saveData($filepath,$records);

            $this->_totalRowsPerWebsite[$website] = $totalRows;
            $time2 = microtime(true);
            $totalTime = date('H:i:s', $time2 - $time1);
            echo "script execution time on website ID {$websiteName}: " . $totalTime . PHP_EOL;

            $this->_websiteBenchmark[] = array(
                'website' => $websiteName,
                'total_record' => $totalRows,
                'time' => $totalTime,
                'sample_data' => "{$baseUrl}getsamplefeed.php?file={$filename}&key={$encrypted_data}"
            );
        }


        $this->_sendEmail();

    }

    /**
     * @throws Mage_Core_Exception
     */
    protected function _sendEmail()
    {

        $record =  $this->_websiteBenchmark;

        $senderEmail = 'noreply@allmoparparts.com';
        $to = $this->_emailRecipient;
        $subject = "VIP Feed Generation";
        $tableBody = "";
        foreach ($record as $item){
            $tableBody .= "<tr>";
            $tableBody .= "<td><b>Website</b></td>";
            $tableBody .= "<td>{$item['website']}</td>";
            $tableBody .= "</tr>";
            $tableBody .= "<tr>";
            $tableBody .= "<td>Total Rows</td>";
            $tableBody .= "<td>{$item['total_record']}</td>";
            $tableBody .= "</tr>";
            $tableBody .= "<tr>";
            $tableBody .= "<td>Time Finished</td>";
            $tableBody .= "<td>{$item['time']}</td>";
            $tableBody .= "</tr>";
            $tableBody .= "<tr>";
            $tableBody .= "<td>Sample Data</td>";
            $tableBody .= "<td><a href=" . $item['sample_data'] . " target='_blank'>Click to Download<a/></td>";
            $tableBody .= "</tr>";
            $tableBody .= "<tr><td></td></tr>";
        }

        $message = "<html>
                    <body>
                    <table>{$tableBody}</table>
                    </body>
                    </html>
                    ";

        $mail = Mage::getModel('core/email');
        $mail->setToName('Growth Rocket Team');
        $mail->setToEmail($to);
        $mail->setBody($message);
        $mail->setSubject($subject);
        $mail->setFromEmail($senderEmail);
        $mail->setFromName("VIP Feeds");
        $mail->setType('html');

        try {
            $mail->send();
            Mage::log($record, null, 'feed-email.log');
        }
        catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param $revenue
     * @return int|string
     */
    protected function _grossSellingLabel($revenue)
    {
        $revenue = floor($revenue);
        $grossLabel = array();
        $grossLabel['Lowest-Grossing'] = array('min' => 0, 'max' => 50);
        $grossLabel['Low-Grossing'] = array('min' => 51, 'max' => 100);
        $grossLabel['Mid-Grossing'] = array('min' => 101, 'max' => 300);
        $grossLabel['High-Grossing'] = array('min' => 301, 'max' => 499);
        $grossLabel['Highest-Grossing'] = array('min' => 500, 'max' => '');

        foreach ($grossLabel as $key => $data){
            if($revenue >= $data['min'] && empty($data['max'])){
                return $key;
            }elseif($revenue >= $data['min'] && $revenue <= $data['max']){
                return $key;
            }
        }
    }

    function _getConnection($type = 'core_read')
    {
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    function _getTableName($tableName)
    {
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    function _getEntityTypeId($entity_type_code = 'catalog_product'){
        $connection = $this->_getConnection('core_read');
        $sql		= "SELECT entity_type_id FROM " .$this->_getTableName('eav_entity_type') . " WHERE entity_type_code = ?";
        return $connection->fetchOne($sql, array($entity_type_code));
    }

    /**
     * @param string $attribute_code
     * @return mixed
     */
    function _getAttributeId($attribute_code = 'price')
    {
        $connection = $this->_getConnection('core_read');
        $sql = "SELECT attribute_id FROM " . $this->_getTableName('eav_attribute') . " WHERE entity_type_id = ? AND attribute_code = ?";
        $entity_type_id = $this->_getEntityTypeId();
        return $connection->fetchOne($sql, array($entity_type_id, $attribute_code));
    }

    protected function _getStockStatus()
    {
        if(Mage::helper('shoppingfeed')->isEnableFilterProduct()) {
            return "(1)";
        }else {
            return "(0,1)";
        }
    }

    /**
     * @param $id
     * @return mixed|string
     */
    protected function _getCategoryLabelById($id)
    {
        $customCategoryLabel = "";
        if(array_key_exists($id, $this->_customCategory)){
            $customCategoryLabel = $this->_customCategory[$id];
        }

        return $customCategoryLabel;
    }

    /**
     * @return array
     */
    protected function _getCustomerCategory()
    {
        $customOption = array();
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'auto_type');
        $allOptions = $attribute->getSource()->getAllOptions(true, true);
        foreach ($allOptions as $instance) {
            $customOption[$instance['value']] = $instance['label'];
        }

        return $customOption;
    }

    public function getWebsiteBaseUrl($websiteId){
        $website = Mage::getModel('core/website')->load($websiteId);
        $url = Mage::getStoreConfig('web/unsecure/base_url', $website->getDefaultStore());
        $useSecure = Mage::getStoreConfig('web/secure/use_in_frontend',$website->getDefaultStore());
        if($useSecure){
            $url = Mage::getStoreConfig('web/secure/base_url', $website->getDefaultStore());
        }
        return $url;
    }
    private function _getFeedColumns(){
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
            'shipping_label',
            'custom_label_0',
            'custom_label_1',
            'custom_label_2',
            'custom_label_3'
        );
        return $columns;
    }
    public function getCategory($categorytxt){
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
            'subaru gear and accessories' => 2495,
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
        $key = strtolower($categorytxt);
        if(array_key_exists($key,$map)){
            return $map[$key];
        }
        return '';
    }
    public function getPrice($array){
        $price = '';
        $requiredKeys = array('price','final_price','special_price','cbprice');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)){
            $finalpriceInt = round($array['final_price'],2) * 100;
            $specialpriceInt = round($array['special_price'], 2) * 100;
            //if no special price check cbprice
            if($finalpriceInt == $specialpriceInt || is_null($array['special_price'])){
                // Prioritize the cbprice even if the price is not null
                if(is_numeric($array['cbprice'])){
                    $price = number_format($array['cbprice'],2) . ' USD';
                }else{
                    //Cost based price is not available
                    $price = number_format($array['price'], 2) . ' USD';
                }
            }else{
                //Use the final price
                $price = number_format($array['final_price'],2) . ' USD';
            }
        }
        return $price;
    }
    public function getSpecialPrice($array){
        $specialprice = '';
        $requiredKeys = array('special_price','final_price');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)){
            if(!is_null($array['special_price'])){
                $sprice = floatval($array['special_price']);
                $fprice = floatval($array['final_price']);
                //If the final price is equal to special price then the special price is active
                if($sprice == $fprice){
                    $specialprice = number_format($fprice,2) . ' USD';
                }
            }
        }
        return $specialprice;
    }
    public function fetchAdditionalImages($array,$baseUrl){
        $additionalLink = '';
        $link = array();
        $requiredKeys = array('sku','year_label','make_label','model_label','additional_image_link');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)){
            $sku = array_shift($array);
            $additional = explode(',',array_pop($array));
            if(count($additional) > 0){
                $fitmentSerial = '';
                foreach($array as $fitment){
                    $fitmentSerial.= str_replace('-','',$this->optionHelper->filterTextToUrl($fitment));
                }
                if($fitmentSerial !==''){
                    foreach($additional as $item){
                        $cleanLink  = $this->_mediaUrl . 'catalog/product' . $item . '?' . $fitmentSerial . $sku;
                        array_push($link,$cleanLink);
                    }
                }
            }
        }
        $additionalLink = implode(',', $link);
        return $additionalLink;
    }


    public function getImageLink($array = array(), $baseurl)
    {
        $imageLink = '';
        $requiredKeys = array('sku','year_label','make_label','model_label','image');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)){
            $image = array_pop($array);
            $reverse = array_reverse($array);
            $sku = array_pop($reverse);
            $fitments = array_reverse($reverse);
            $fitmentSerial = '';
            foreach($fitments as $fitment){
                $fitmentSerial.= str_replace('-','',$this->optionHelper->filterTextToUrl($fitment));
            }
            if($image !=='no_selection'){
                $imageLink = $image. '?' . $fitmentSerial . $sku;
            }

        }
        if($imageLink !==''){

            if(!preg_match("/^\//", $imageLink)){
                $imageLink = "/" . $imageLink;
            }
            return $this->_mediaUrl . 'catalog/product' . $imageLink;
        }else{
            return '';
        };
    }

    public function getYmmsLink($ymm, $baseUrl)
    {
        $url = '';
        $requiredKeys = array('sku','year_label','make_label','model_label');
        $keys = array_keys($ymm);

        if($this->keysMatches($requiredKeys,$keys)){
            $reverse = array_reverse($ymm);
            unset($reverse['custom_url_key']);
            $sku = array_pop($reverse);
            $fitments = array_reverse($reverse);
            $fitmentUrl = array();
            foreach($fitments as $key => $fitment){
                array_push($fitmentUrl,$this->optionHelper->filterTextToUrl($fitment));
            }
            $fitmentPath = (implode('-',$fitmentUrl));
            if(!empty($ymm['custom_url_key'])){
                $sku = $ymm['custom_url_key'];
            }
            $sku = str_replace(' ','--', $sku);

            $url = $baseUrl . 'sku-ymm/' . $fitmentPath . '/' . $sku . '.html';
        }
        return $url;
    }
    public function getDescription($array = array()){
        $description = '';
        $requiredKeys = array('sku','store_name', 'default_name','part_name','year_label','make_label','model_label');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)){
            $reverse = array_reverse($array);
            $sku = array_pop($reverse);
            $store_name = array_pop($reverse);
            $default_name = array_pop($reverse);
            $partName = str_replace('â€šÃ„Ã£','',preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags(array_pop($reverse))));
            $name = is_null($store_name) ? $default_name : $store_name;
            $fitment = implode(' ', array_reverse($reverse));

            $description = sprintf('Genuine %s %s. OEM # %s. Shop for Original Factory %s.',$fitment, $partName, $sku,$name);
        }
        return $description;
    }

    public function getTitle($array = array(), $websiteLabel = null)
    {

        $title = '';
        $requiredKeys = array('year_label','make_label','model_label','part_name','sku');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)){
            //Reverse array to get sku,part name vs array_shift
            $reverse = array_reverse($array);
            $sku = array_pop($reverse);
            $partname = array_pop($reverse);
            //Reverse array to get year-make-model order
            $fitment = implode(' ',array_reverse($reverse));
            $filteredPartname = preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($partname));
            if(!empty($websiteLabel)){
                $prefix = "Mopar Genuine";
                $title = sprintf('%s %s %s. OEM # %s',$fitment,$prefix,$filteredPartname,$sku);
            }else{
                $title = sprintf('%s %s. OEM # %s',$fitment,$filteredPartname,$sku);
            }

        }
        return $title;
    }


    public function getId($array = array())
    {
        $id = '';
        $requiredKeys = array('sku','year_label','make_label','model_label');
        $keys = array_keys($array);
        if($this->keysMatches($requiredKeys,$keys)) {
            $filteredValues = array();
            foreach ($array as $key => $value) {
                if (in_array($key, $requiredKeys)) {
                    array_push($filteredValues, $this->optionHelper->filterTextToUrl($value));
                }
            }
            $id = str_replace('-', '', strip_tags(strtolower(implode('', $filteredValues))));
        }
        return $id;
    }

    private function keysMatches($requiredKeys, $keys)
    {
        $matchedKeys = array_intersect($requiredKeys,$keys);
        return count($matchedKeys) === count($requiredKeys);
    }

    private function _segment($array, $key)
    {
        return array_intersect_key($array,array_flip($key));
    }

    private function _createGoogleCategory($websiteId)
    {
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $conn->getConnection('core_write');
        $tablename = sprintf('feed_category_%s',$websiteId);
        /** @var Varien_Db_Ddl_Table $table */
        $table = $conn->getConnection('core_write')->newTable($tablename);
        $table->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
            'unsigned' => true,
            'nullable' => false,
        ))
            ->addColumn('category', Varien_Db_Ddl_Table::TYPE_VARCHAR,null);
        $writer->createTemporaryTable($table);

        $str = 'SELECT w.product_id,c.value FROM catalog_product_website as w
LEFT JOIN catalog_product_entity_varchar as c
ON w.product_id = c.entity_id
WHERE w.website_id =%d and c.attribute_id=251
ORDER BY 1';
        $select = sprintf($str,$websiteId);
        $results = $writer->fetchAll($select);
        $records = array();
        foreach($results as $result){
            $categories = explode(',',$result['value']);
            $category = null;
            //get first record only
            if(count($categories) > 1){
                $category = array_shift($categories);
            }else{
                $category = array_pop($categories);
            }
            $record = array(
                'product_id' => $result['product_id'],
                'category' => $category
            );
            array_push($records,$record);
        }
        $writer->insertArray($tablename,array('product_id','category'),$records);
    }

    private function _createNameIndex($websiteId)
    {
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $conn->getConnection('core_write');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $conn->getConnection('core_read');

        /** @var Mage_Core_Model_Resource_Config $eavConn */
        $eavConn = Mage::getResourceModel('core/config');

        $tablename = sprintf('feed_name_index_%s',$websiteId);
        $storeTable = $conn->getTableName('core/store');
        $productWebsiteTable = $conn->getTableName('catalog/product_website');
        $varAttrTable = $eavConn->getValueTable('catalog/product','varchar');

        /** @var Varien_Db_Ddl_Table $table */
        $table = $conn->getConnection('core_write')->newTable($tablename);


        $table->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
            'unsigned' => true,
            'nullable' => false,
        ))->addColumn('default_value', Varien_Db_Ddl_Table::TYPE_TEXT,array(
            'nullable' => false,
        ))->addColumn('store_value', Varien_Db_Ddl_Table::TYPE_TEXT,array(
            'nullable' => false,
        ))->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT,array(
            'nullable' => false,
            'unsigned' => true,
        ))->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, array(
            'unsigned' => true,
            'nullable' => false,
        ));

        $writer->createTemporaryTable($table);
        /** @var Varien_Db_Statement_Pdo_Mysql $result */
        $storeIds = $reader->select()->from($storeTable,'store_id')
            ->where('website_id = ?', $websiteId)
            ->query()->fetchAll();

        foreach($storeIds as $storeId){
            $subquery = $reader->select();
            $select = $reader->select();

            $subquery->from(array('i' => $varAttrTable), array('value'))
                ->where('i.store_id = ?', $storeId)
                ->where('i.attribute_id =?', self::NAME_ATTRIBUTE_ID)
                ->where('i.entity_id = o.entity_id');
            $staticStoreId = sprintf(" '%s' as store_id", $storeId);
            $select->from(array('o' => $varAttrTable),array(
                'entity_id' => 'entity_id',
                'store_value' => $subquery,
                'default_value' => 'value',
                'store_id' => new Zend_Db_Expr($reader->quote($storeId))
            ))
                ->join(array('w' => $productWebsiteTable),'o.entity_id = w.product_id',array('website_id'))
                ->where('o.attribute_id= ?', self::NAME_ATTRIBUTE_ID)
                ->where('o.store_id = ?', 0)
                ->where('w.website_id = ? ', $websiteId);
            $result = $reader->fetchAll($select);
            $writer->insertArray($tablename,array('entity_id','store_value', 'default_value', 'store_id', 'website_id'), $result);
        }
    }

    private function _createPriceIndex($websiteId)
    {
        /** @var Mage_Core_Model_Resource_Config $eavConn */
        $eavConn = Mage::getResourceModel('core/config');

        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $conn->getConnection('core_write');

        $tablename = sprintf('feed_price_index_%s',$websiteId);
        $decimalAttrTable = $eavConn->getValueTable('catalog/product','decimal');
        $storeTable = $conn->getTableName('core/store');

        /** @var Varien_Db_Ddl_Table $table */
        $table = $conn->getConnection('core_write')->newTable($tablename);

        $table->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
            'unsigned' => true,
            'nullable' => false,
        ))
            ->addColumn('final_price', Varien_Db_Ddl_Table::TYPE_FLOAT,null)
            ->addColumn('price', Varien_Db_Ddl_Table::TYPE_FLOAT,null)
            ->addColumn('special_price', Varien_Db_Ddl_Table::TYPE_FLOAT,null)
            ->addColumn('cbprice', Varien_Db_Ddl_Table::TYPE_FLOAT,null)
            ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT,array(
                'unsigned' => true,
            ))
            ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT,array(
                'unsigned' => true,
            ));

        $writer->createTemporaryTable($table);

        $defaultSpQuery = $writer->select()
            ->from(array('dsp' => $decimalAttrTable),array('value'))
            ->where('dsp.attribute_id = ?', self::SPECIAL_PRICE_ATTRIBUTE_ID)
            ->where('dsp.store_id = ?', 0)
            ->where('dsp.entity_id = e.entity_id')
            ->where('dsp.value IS NOT NULL');

        $storeIds = $writer->select()->from($storeTable,'store_id')
            ->where('website_id = ?', $websiteId)
            ->query()->fetchAll();

        foreach($storeIds as $storeId){
            $storeSpQuery = $writer->select()
                ->from(array('ssp' => $decimalAttrTable),array('value'))
                ->where('ssp.attribute_id = ?', self::SPECIAL_PRICE_ATTRIBUTE_ID)
                ->where('ssp.store_id = ?', $storeId)
                ->where('ssp.entity_id = e.entity_id')
                ->where('ssp.value IS NOT NULL');
            $spQuery = $writer->select()
                ->union(array($storeSpQuery,$defaultSpQuery))
                ->limit(1);

            $cbpQuery = $writer->select()
                ->from(array('a' => 'aitoc_aitcbp_product_price_index'),array('price'))
                ->where('a.product_id = e.entity_id')
                ->where('a.website_id = ?', $websiteId);

            $allQuery = $writer->select()
                ->from(array('e' => 'catalog_product_entity'), array(
                    'entity_id' => 'e.entity_id',
                    'special_price' => $spQuery,
                    'cbprice' => $cbpQuery,
                    'store_id ' => new Zend_Db_Expr($writer->quote($storeId)),
                    'website_id ' => new Zend_Db_Expr($writer->quote($websiteId)),
                ))
                ->join(array('c' => 'catalog_product_index_price'),'e.entity_id = c.entity_id', array(
                    'price' => 'c.price',
                    'final_price' => 'c.final_price'
                ))
                ->joinLeft(array('p' => $decimalAttrTable),'e.entity_id = p.entity_id',array('entity_id'))
                ->where('c.website_id = ?', $websiteId)
                ->where('c.customer_group_id = ?',self::GENERAL_CUSTOMER_GROUP_ID)
                ->group(new Zend_Db_Expr(1));
            $result = $writer->fetchAll($allQuery);
            $writer->insertArray($tablename,array('entity_id','special_price','cbprice','store_id','website_id','price','final_price'),$result);
        }
    }

    protected function _salesGrossData($websiteId)
    {
        $conn = Mage::getSingleton('core/resource');
        $writer = $conn->getConnection('core_write');

        $tableName = "feed_gross_sales_{$websiteId}";
        $table = $conn->getConnection('core_write')->newTable($tableName);
            $table->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
                'unsigned' => true,
                'nullable' => false,
            ))
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER,null)
        ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER,null)
        ->addColumn('qty_ordered', Varien_Db_Ddl_Table::TYPE_DECIMAL,null)
        ->addColumn('revenue', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_VARCHAR,null);

        if($this->getArg('demo-date')){
            $startMonth = Mage::getModel('core/date')->date("{$this->getArg('demo-date')}-1 00:00:00");
            $endMonth = Mage::getModel('core/date')->date("{$this->getArg('demo-date')}-31 23:59:59");
        }else{
            $startMonth = Mage::getModel('core/date')->date("Y-m-01 00:00:00",strtotime('-1 month'));
            $endMonth = Mage::getModel('core/date')->date("Y-m-31 23:59:59",strtotime('-1 month'));
        }
        
        $writer->createTemporaryTable($table);
        $query = "SELECT sales_item.store_id,cs.website_id, product_id,sum(qty_ordered) as qty_ordered,sum(row_total_incl_tax) as revenue, sales_order.status as status FROM sales_flat_order_item as sales_item
                    JOIN sales_flat_order as sales_order ON sales_order.entity_id = sales_item.order_id
                    LEFT JOIN core_store as cs ON cs.store_id = sales_item.store_id WHERE sales_order.status = 'complete' AND cs.website_id = {$websiteId} AND 
                   sales_order.created_at between '{$startMonth}' AND '{$endMonth}'
                     GROUP By store_id,product_id";

        $results = $writer->fetchAll($query);
        if(!empty($results)){
            $writer->insertArray($tableName,array('store_id','website_id','product_id','qty_ordered','revenue','status'),$results);
        }
    }

    protected function _createYmmTable($websiteId, $fitmentTable, $optionValue)
    {

        $websiteCode = $this->_websiteCodes[$websiteId];
        $value = Mage::getConfig()->getNode("websites/{$websiteCode}/fitment/configuration/make");
        $includedMake = $value[0];

        $conn = Mage::getSingleton('core/resource');
        $writer = $conn->getConnection('core_write');
        $tablename = "feed_ymm_{$websiteId}";

        $table = $conn->getConnection('core_write')->newTable($tablename);
        $table->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
            'unsigned' => true,
            'nullable' => false,
        ))
            ->addColumn('year', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('make', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('model', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('year_label', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('make_label', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('model_label', Varien_Db_Ddl_Table::TYPE_VARCHAR,null);

        $writer->createTemporaryTable($table);


        $condition = "";
        $makeArray = array(
            3 => 'Jeep',
            4 => 'Ram'
        );

        if(array_key_exists($websiteId, $makeArray)){
            $q = "select option_id from {$optionValue} WHERE value = '{$makeArray[$websiteId]}'";
            $qResult = $writer->fetchOne($q);
            if(!empty($qResult)){
                $condition = " and c.make = {$qResult} ";
            }
        }


        $select = "SELECT distinct(w.product_id) as product_id, c.year as year, c.make as make, c.model as model, d.value as year_label, e.value as make_label, f.value as  model_label   FROM catalog_product_website as w
                JOIN {$fitmentTable} as c ON w.product_id = c.product_id
                LEFT JOIN {$optionValue} as d ON c.year = d.option_id and d.store_id = 0
                LEFT JOIN {$optionValue} as e ON c.make = e.option_id and e.store_id = 0
                LEFT JOIN {$optionValue} as f ON c.model = f.option_id and f.store_id = 0
                WHERE w.website_id = {$websiteId} AND c.make IN ({$includedMake}) ORDER BY 1";


        $results = $writer->fetchAll($select);
        $writer->insertArray($tablename,array('product_id','year','make','model','year_label','make_label','model_label'),$results);
    }

    protected function _createCustomAttributeTable($websiteId, $varAttrTable, $customUrlKey, $partName, $image, $sHQDimension,$freeShipping, $isOversized)
    {
        $conn = Mage::getSingleton('core/resource');
        $writer = $conn->getConnection('core_write');
        $tablename = "feed_custom_attribute_{$websiteId}";

        $table = $conn->getConnection('core_write')->newTable($tablename);
        $table->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
            'unsigned' => true,
            'nullable' => false,
        ))
            ->addColumn('custom_url_key', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('part_name', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('image', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('shipperhq_dim_group', Varien_Db_Ddl_Table::TYPE_INTEGER,null)
            ->addColumn('free_shipping_product', Varien_Db_Ddl_Table::TYPE_INTEGER,null)
            ->addColumn('is_oversized', Varien_Db_Ddl_Table::TYPE_INTEGER,null);

        $writer->createTemporaryTable($table);

        $select = "SELECT distinct(w.product_id) as product_id,c.value as custom_url_key, d.value as part_name, e.value as image, f.value as shipperhq_dim_group, g.value as free_shipping_product, h.value as is_oversized FROM catalog_product_website as w
                LEFT JOIN {$varAttrTable} as c ON w.product_id = c.entity_id and c.attribute_id={$customUrlKey}
                LEFT JOIN {$varAttrTable} as d ON w.product_id = d.entity_id and d.attribute_id={$partName}
                LEFT JOIN {$varAttrTable} as e ON w.product_id = e.entity_id and e.attribute_id={$image}
                LEFT JOIN {$varAttrTable} as f ON w.product_id = f.entity_id and f.attribute_id={$sHQDimension}
                LEFT JOIN catalog_product_entity_int as g ON w.product_id = g.entity_id and g.attribute_id={$freeShipping}
                LEFT JOIN catalog_product_entity_int as h ON w.product_id = h.entity_id and h.attribute_id={$isOversized}
                WHERE w.website_id = {$websiteId} ORDER BY 1";
        

        $results = $writer->fetchAll($select);
        $writer->insertArray($tablename,array('product_id','custom_url_key','part_name', 'image','shipperhq_dim_group','free_shipping_product','is_oversized'),$results);
    }

    protected function _createdimensionIndex($websiteId,$decimalAttrTable, $shipLength, $shipHeight, $shipWidth, $productWeight)
    {

        $conn = Mage::getSingleton('core/resource');
        $writer = $conn->getConnection('core_write');
        $tablename = "feed_dimension_{$websiteId}";

        $table = $conn->getConnection('core_write')->newTable($tablename);
        $table->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
            'unsigned' => true,
            'nullable' => false,
        ))
            ->addColumn('ship_length', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('ship_height', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('ship_width', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
            ->addColumn('weight', Varien_Db_Ddl_Table::TYPE_VARCHAR,null);

        $writer->createTemporaryTable($table);

        $select = "SELECT w.product_id,c.value as ship_length, d.value as ship_height, e.value as ship_width, f.value as weight FROM catalog_product_website as w
                LEFT JOIN {$decimalAttrTable} as c ON w.product_id = c.entity_id and c.attribute_id={$shipLength}
                LEFT JOIN {$decimalAttrTable} as d ON w.product_id = d.entity_id and d.attribute_id={$shipHeight}
                LEFT JOIN {$decimalAttrTable} as e ON w.product_id = e.entity_id and e.attribute_id={$shipWidth}
                LEFT JOIN {$decimalAttrTable} as f ON w.product_id = f.entity_id and f.attribute_id={$productWeight}
                WHERE w.website_id = {$websiteId} ORDER BY 1";

        $results = $writer->fetchAll($select);
        $writer->insertArray($tablename,array('product_id','ship_length','ship_height','ship_width', 'weight'),$results);
    }

    private function _createAdditionalImagesTempTable()
    {
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $conn->getConnection('core_write');
        /** @var Varien_Db_Ddl_Table $table */
        $table = $conn->getConnection('core_write')->newTable('feed_additional_images');

        $table->addColumn('id',Varien_Db_Ddl_Table::TYPE_INTEGER, null,array(
            'identity' => true,
            'unsigned' => true,
            'primary'  => true,
            'nullable' => false,
        ))->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
        ))->addColumn('additional_images', Varien_Db_Ddl_Table::TYPE_TEXT, null);

        $writer->createTemporaryTable($table);
        $selectStr = 'SELECT e.entity_id as product_id,(SELECT GROUP_CONCAT(m.value) FROM catalog_product_entity_media_gallery as m where m.entity_id = e.entity_id GROUP BY m.entity_id ) as additional_images
FROM catalog_product_entity as e';
        $result = $writer->fetchAll($selectStr);
        $writer->insertArray('feed_additional_images',array('product_id', 'additional_images'),$result);
    }

    protected function _parseArgParams($string)
    {
        $websites = array();
        if(!empty($string)){
            $websites = explode(',', $string);
        }
        return $websites;
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();