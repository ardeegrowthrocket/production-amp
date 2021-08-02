<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/16/17
 * Time: 7:53 PM
 */

class Homebase_Autopart_Helper_Data extends Mage_Core_Helper_Abstract {
    private $storeCode;

    /** @var Mage_Core_Model_Resource $_resource */
    protected $_resource;

    /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
    protected $_reader;


    private $order;

    private $direction;
    public function __construct()
    {
        $this->storeCode = Mage::app()->getStore()->getCode();
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_reader = $this->_resource->getConnection('core_read');
    }

    public function getModelPath($string){
        $model = strtolower($string);
        $baseUrl = Mage::app()->getStore($this->storeCode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $baseUrl.= 'model/' .$model .'.html';
        return $baseUrl;
    }

    public function getYearPath($string){
        $model = strtolower($string);
        $baseUrl = Mage::app()->getStore($this->storeCode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $baseUrl.= 'year/' .$model .'.html';
        return $baseUrl;
    }

    public function getMakePath($string){
        $make = strtolower($string);
        $baseUrl = Mage::app()->getStore($this->storeCode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $baseUrl.= 'make/' .$make .'.html';
        return $baseUrl;
    }

    public function getSkuPath($string, $customBaseUrl = null, $storeId = null){
        $sku = $string;
        $sku = str_replace(' ','--',$sku);

        if(!empty($customBaseUrl)){
            $baseUrl = $customBaseUrl;
        }else{
            if(!empty($storeId)){
                $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            }else{
                $baseUrl = Mage::app()->getStore($this->storeCode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            }
        }
        $baseUrl.= 'sku/' .$sku .'.html';
        return $baseUrl;
    }
    public function getYmmPath(){
        $baseUrl = Mage::app()->getStore($this->storeCode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $baseUrl.= 'year/';
        return $baseUrl;
    }
    public function getHajaxPath(){
        return Mage::app()->getStore($this->storeCode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . 'hajax';
    }
    public function getQuery(){
        return $this->_getRequest()->getParam('query');
    }
    public function getYmmQuery(){
        $params = unserialize($this->_getRequest()->getParam('query'));
        return $params;
    }
    public function getMakeModelQuery(){
        $params = unserialize($this->_getRequest()->getParam('query'));
        $processed = array();
        $model  = array();
        foreach($params as $ndx => $value){
            if($ndx < 1){
                $processed[] = $value;
            }else{
                $model[] = $value;
            }
        }
        if(!empty($model)){
            $processed[] = implode(' ', $model);
        }
        return $processed;
    }

    public function getOptionValue($id){
        /** @var String $table */
        $table = $this->_resource->getTableName('eav/attribute_option_value');

        $query = 'SELECT value FROM ' . $table . ' WHERE option_id = :id AND store_id = :store';
        $statement = $this->_reader->query($query ,array(
            'id' => $id,
            'store'  => 0
        ));
        $result = $statement->fetch();
        return $result['value'];
    }

    public function getAttributeOptionId($value,$attrId = ''){

        $table = $this->_resource->getTableName('eav/attribute_option_value');
        $attributeOptionTable = $this->_resource->getTableName('eav/attribute_option');
        //Please add check if option belongs to the attribute's option values
        if($attrId !== ''){
            $attQuery = 'SELECT option_id FROM ' . $attributeOptionTable . ' WHERE attribute_id = :attr';
            $attrStatement = $this->_reader->query($attQuery, array('attr' => $attrId));
            $attRes = $attrStatement->fetchColumn(0);
        }
        $query = 'SELECT option_id FROM ' . $table . ' WHERE LOWER(value) = :value AND store_id = :store ';
        if($attrId !== ''){
            $attQuery = 'SELECT option_id FROM ' . $attributeOptionTable . ' WHERE attribute_id = :attr';
            $attrStatement = $this->_reader->query($attQuery, array('attr' => $attrId));
            $raw = $attrStatement->fetchAll();
            $query = $query . ' AND option_id IN (:option1)';
            $attRes = array();
            foreach($raw as $ndx => $row){
                $attRes[] = (int) $row['option_id'];
            }
            $select = $this->_reader->select()
                ->from($table)
                ->columns('option_id')
                ->where('option_id IN (?)',$attRes)
                ->where('LOWER(value) = ?', $value)
                ->where('store_id = ?', 0);
            $statement= $select->query();
        }else{
            $statement = $this->_reader->query($query ,array(
                'value' => $value,
                'store'  => 0
            ));
        }
        $result = $statement->fetch();
        return $result['option_id'];
    }

    public function getLabelOptionId($label){
        /** @var Homebase_Autopart_Model_Resource_Label_Collection $labels */
        $labels = Mage::getModel('hautopart/label')->getCollection();
        $labels->addExpressionFieldToSelect('llabel','LOWER(label)', 'label')
            ->getSelect()->having('llabel = ?', $label);
        $item = $labels->fetchItem();
        if($item){
            return $item->getOption();
        }
        return -1;
    }

    public function getAvailableMakeModels($makeId)
    {
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_collection */
        $_collection = Mage::getModel('hautopart/mix')->getCollection();
        $_collection->addFieldToFilter('make', $makeId);
        $_collection->getSelect()->group('model');
        return $_collection->getColumnValues('model');
    }

    public function getAvailableYearRange($make, $model){
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_collection */
        $_collection = Mage::getModel('hautopart/mix')->getCollection();
        $_collection->addFieldToFilter('make', $make);
        $_collection->addFieldToFilter('model', $model);
        $_collection->getSelect()->group('year');
        $yearIds = $_collection->getColumnValues('year');

        $yearLabels = array();
        foreach($yearIds as $year){
            $yearLabels[] = $this->getOptionValue($year);
        }
        asort($yearLabels);
        $low = array_shift($yearLabels);
        $high = array_pop($yearLabels);
        return $low . '-' . $high;
    }

    public function scrubLabel($label){
        $conditions = array(
            array(
                'needle'    => '&',
                'replace'   => 'and'
            ),
//            array(
//                'needle'    => '/',
//                'replace'   => 'and'
//            ),
            array(
                'needle'    => '-',
                'replace'   => ''
            )
        );
        foreach($conditions as $condition){
            $label  = str_replace($condition['needle'],$condition['replace'],$label);
        }
        $parts = explode(' ', $label);
        $parts = array_filter($parts);
        $label = implode('-', $parts);
        return $label;
    }

    /**
     * @param $dataCollection Varien_Data_Collection
     */
    public function sortCollection($dataCollection, $column = null, $dir = 'ASC'){
        $sortedCollection = new Varien_Data_Collection();
        $this->setOrder($column);
        $this->setDirection($dir);

        $unsortedArray = $dataCollection->toArray();
        if(array_key_exists('items', $unsortedArray)){
            $unsortedArray = $unsortedArray['items'];
            $year = array();
            $model = array();
            foreach($unsortedArray as $key => $value){
                $year[$key] = $value['year'];
                $model[$key] = $value['model'];
            }
            if(array_multisort($model, SORT_DESC, $year, SORT_DESC, $unsortedArray)){
                foreach($unsortedArray as $sortedArray){
                    $tmpObject = new Varien_Object();

                    $tmpObject->addData(array(
                        'combination' => $sortedArray['combination'],
                        'year' => $sortedArray['year'],
                        'make' => $sortedArray['make'],
                        'model' => $sortedArray['model'],
                    ));
                    $sortedCollection->addItem($tmpObject);
                }
            }
            return $sortedCollection;
        }
        return;
    }

    /**
     * @param $dataCollection Varien_Data_Collection
     */
    public function sortGenericCollection($dataCollection, $dir = SORT_ASC){
        $sortedCollection = new Varien_Data_Collection();
        $unsortedArray = $dataCollection->toArray();
        if(array_key_exists('items', $unsortedArray)){
            $unsortedArray = $unsortedArray['items'];
            $label = array();
            foreach($unsortedArray as $key => $value){
                $label[$key] = $value['option_label'];
            }
            if(array_multisort($label, $dir, SORT_STRING, $unsortedArray)){
                foreach($unsortedArray as $sortedArray){
                    $tmpObject = new Varien_Object();
                    $tmpObject->addData(array(
                        'option_id' => $sortedArray['option_id'],
                        'option_label' => $sortedArray['option_label'],
                        'scrubbed_label' => $sortedArray['scrubbed_label'],
                        'link' => $sortedArray['link'],
                    ));
                    $sortedCollection->addItem($tmpObject);
                }
            }
            return $sortedCollection;
        }
        return;
    }

    /**
     * @param $objectA Varien_Object
     * @param $objectB Varien_Object
     * @return bool|int
     */
    public function sortVarienObject($objectA, $objectB)
    {
        $column = $this->getOrder();
        if($this->getDirection() == 'DESC'){
            return strcmp($objectA->getData($column), $objectB->getData($column)) < 0;
        }else{
            return strcmp($objectA->getData($column), $objectB->getData($column));
        }
    }
    public function setOrder($order){
        $this->order =  $order;
    }

    public function getOrder(){
        return $this->order;
    }

    public function setDirection($dir){
        $this->direction = $dir;
    }

    public function getDirection(){
        return $this->direction;
    }

    public function ymmMatchesFitment($fitment,$productId){
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $fimentCollection */
        $fimentCollection = Mage::getModel('hautopart/mix')->getCollection();

        foreach($fitment as $ndx=>$value){
            $fimentCollection->addFieldToFilter($ndx,$value);
        }
        $fimentCollection->addFieldToFilter('product_id',$productId);
        return $fimentCollection->count();
    }
    /**
     * Get Table name based on alias if available
     * @param $alias
     * @return string
     */
    public function _getTable($alias){
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getTableName($alias);
    }

    /**
     * Get Adapter Reader object for executing native SQL
     * @return Varien_Db_Adapter_Interface
     */

    public function _getReader(){
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getConnection('core_read');
    }

    public function getUniversalProducts()
    {
        $productIds = array();
        $storeId = Mage::app()->getStore()->getId();
        $cacheId = 'universal_products_' . $storeId;

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $productIds = unserialize($data_to_be_cached);

        } else {

        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToFilter('is_universal', 1);
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', array(
            'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
        ));


        foreach ($collection as $product) {
            $productIds[] = $product->getId();
        }

            Mage::app()->getCache()->save(serialize($productIds), $cacheId);
        }
        return $productIds;
    }

    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isCategoryUniversal($categoryId)
    {

        $storeId = Mage::app()->getStore()->getId();
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('entity_id');
        $collection->addAttributeToFilter('is_universal', 1);
        $collection->addAttributeToFilter('auto_type', array('finset' => $categoryId));
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToFilter('visibility', array(
            'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
        ));

        return (bool) $collection->getSize();
    }

    public function getCategoryIdByLabel()
    {
        $cacheId = 'category_label_data';
        $categoryLabel = array();

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $categoryLabel = unserialize($data_to_be_cached);

        } else {

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = 'SELECT * FROM ' . $resource->getTableName('hautopart/combination_label') . ' WHERE store_id = 0';
            $results = $readConnection->fetchAll($query);

            foreach ($results as $item) {
                $categoryLabel[strtolower($item['label'])] = $item['option'];
            }

            Mage::app()->getCache()->save(serialize($categoryLabel), $cacheId);
        }

        return $categoryLabel;
    }

    /**
     * Build auto_type to auto_label
     */
    public function buildAutoTypeLabel()
    {
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'auto_type');
        $allOptions = $attribute->getSource()->getAllOptions(true, true);
        foreach ($allOptions as $option) {

            $optionValue = $option['value'];
            $optionLabel = trim($option['label']);

            if (!empty($optionLabel)) {
                $_label = Mage::getModel('hautopart/label')->load($optionValue, 'option');
                try {
                    if (!$_label->getId()) {
                        $_label->setOption($optionValue);
                        $_label->setLabel($optionLabel);
                        $_label->setName($optionLabel);
                        $_label->save();
                    }
                } catch (Exception $exception) {
                    Mage::log($exception->getMessage(), null, 'auto_part.log');
                }
            }
        }
    }

    public function getSortPartName()
    {
        return $configValue = Mage::getStoreConfig('hautopart/settings/sortby');
    }

    public function isEnableRememberFitment()
    {
        return $configValue = Mage::getStoreConfig('hautopart/settings/remember_fitment');
    }


    public function getFitmentProducts()
    {
        $record = array();
        $fitment = Mage::registry('ymm_fitment');
        if(!empty($fitment)){
            $_reader = $this->_resource->getConnection('core_read');
            $select = $_reader->select()->from($this->_resource->getTableName('hautopart/combination_list'));

            foreach($fitment as $key => $value){
                $select->where($key . '= ?', $value);
            }
            $select->group('product_id');
            $result = $select->query();
            $record =  $result->fetchAll(PDO::FETCH_COLUMN,1);

        }

        return $record;
    }

    /**
     * Check if category is excluded
     * @param $categoryId
     * @return bool
     */
    public function isAutoTypeExcluded($categoryId)
    {
        $excludedCategory = array();
        $categoryConfigList =  Mage::getStoreConfig('hautopart/settings/exclude_cat');
        if(!empty($categoryConfigList)){
            $excludedCategory = explode(',',$categoryConfigList);
        }

        if(in_array($categoryId, $excludedCategory)){
            return true;
        }else{
            return false;
        }
    }

    public function customCategoryRedirect()
    {
        if(Mage::app()->getWebsite()->getCode() != 'lfp'){
            return;
        } 

        $data = Mage::getStoreConfig('hauto/settings/custom_redirect');
        $data = json_decode($data,true);

        if(empty($data)){
            return;
        }

        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        foreach ($data as $key => $value){
            if(strpos($currentUrl, $key) !== false){
                $newUrl = str_replace($key,$value,$currentUrl);
                Mage::app()->getResponse()->setRedirect($newUrl, 301)->sendResponse();
                exit;
            }
        }
    }
}