<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 19/10/2018
 * Time: 11:25 AM
 */
require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    const MAKE_OPTION_ID = 305;
    /**
     * Run script
     *
     */
    public function run()
    {
        // TODO: Implement run() method.
        if($this->getArg('stats')){
            $this->getInfo();
        }elseif($this->getArg('report')){
            $csv = new Varien_File_Csv();
            $data = $this->getData()->fetchAll();
            $filename = 'ram_products_'.date('m-d-Y_hia') . '.csv';
            $filepath = $this->getDir() . $filename;
            $csv->saveData($filepath,$data);
        }elseif($this->getArg('assign') && $this->getArg('website')){
            /** @var Mage_Catalog_Model_Product_Website $catalogWebsite */
            $catalogWebsite = Mage::getModel('catalog/product_website');
            // Fetch only product ids
            $product_ids = $this->getData()->fetchAll(PDO::FETCH_COLUMN,0);
            $websites = explode(',',$this->getArg('website'));
            $this->assignProductsToWebsites($product_ids,$websites);
        }elseif($this->getArg('test')){
            echo 'start';
            $model = Mage::getModel('hautopart/fitmentbox');

            $model->build();
            echo 'done script';
        }elseif($this->getArg('find') && $this->getArg('replace') && $this->getArg('store') && $this->getArg('attr')){
            $attribute = $this->getArg('attr');

            /** @var Mage_Catalog_Model_Resource_Product_Collection $_collection */
            $_collection = Mage::getModel('catalog/product')->getCollection();
            $_collection->addStoreFilter($this->getArg('store'));
            $_collection->addAttributeToSelect($attribute);
            $finds = explode(',',$this->getArg('find'));
            foreach($_collection as $_product){
                echo $_product->getId() . "\n";
                $attributeValue = $_product->getData($attribute);
                $replacement = str_ireplace($finds, $this->getArg('replace'), $attributeValue);
                if(strcmp($attributeValue, $replacement) !== 0){
                    $product_reload = Mage::getModel('catalog/product')->load($_product->getId());
                    $product_reload->setStoreId($this->getArg('store'));
                    $product_reload->setData($attribute, $replacement);
                    $productResource = $product_reload->getResource();
                    $productResource->saveAttribute($product_reload,$attribute);
                }
            }
        }
        else{
            $this->usageHelp();
        }
    }
    protected function getInfo(){
        /** @var Homebase_Autopart_Model_Resource_Mix $fitmentResource */
        $fitmentResource = Mage::getResourceModel('hautopart/mix');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $fitmentResource->getReadConnection();

        $results = $reader->select()
            ->from($fitmentResource->getMainTable())
            ->where('make = ?', self::MAKE_OPTION_ID)
            ->query()
            ->fetchAll();
        echo sprintf("Number of Ram products: %d \n", count($results));
        echo sprintf("Number of Ram products based on SQL: %d \n",count($this->getData()->fetchAll()));
    }
    protected function getData(){
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $conn->getConnection('core_read');

        $catalogProductTable = $conn->getTableName('catalog/product');
        $fitmentTable = $conn->getTableName('hautopart/combination_list');
        $optionValue = $conn->getTableName('eav/attribute_option_value');

        $select = $reader->select()
            ->from(array('f' => $fitmentTable),array('product_id'))
            ->join(array('entity' => $catalogProductTable),'entity.entity_id=f.product_id',array('sku'))
            ->join(array('yearlabel' => $optionValue),'f.year=yearlabel.option_id',array('year_label' => 'value'))
            ->join(array('makelabel' => $optionValue),'f.make=makelabel.option_id',array('make_label' => 'value'))
            ->join(array('modellabel' => $optionValue),'f.model=modellabel.option_id',array('model_label' => 'value'))
            ->where('f.make = ?', self::MAKE_OPTION_ID);
        $results = $select->query();
        return $results;
    }
    protected function assignProductsToWebsites($productIds, $websiteIds){
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $conn->getConnection('core_write');

        foreach($websiteIds as $websiteId){
            $productsAssignedCounter = 0;
            foreach($productIds as $productId){
                if (!$productId) {
                    continue;
                }
                if($this->isProductInWebsite($productId, $websiteId)){

                    continue;
                }
                $productsAssignedCounter++;
                $writer->insert($conn->getTableName('catalog/product_website'),array(
                    'product_id' => (int) $productId,
                    'website_id' => (int) $websiteId
                ));
            }

            if($productsAssignedCounter > 0){
                $storeIds = Mage::app()->getWebsite($websiteId)->getStoreIds();
                foreach ($storeIds as $storeId) {
                    $store = Mage::app()->getStore($storeId);
                    $this->_getProductResource()->refreshEnabledIndex($store, $productIds);
                }
            }else{
                echo "NO action needed.\n";
            }
        }
    }
    private function isProductInWebsite($productId, $websiteId){
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $conn->getConnection('core_read');

        $productWebsiteTable = $conn->getTableName('catalog/product_website');

        $select = $reader->select()
            ->from($productWebsiteTable)
            ->where('product_id = ?', $productId)
            ->where('website_id = ?', $websiteId)
            ->query();
        return count($select->fetchAll()) > 0;
    }
    protected function _getProductResource()
    {
        return Mage::getResourceSingleton('catalog/product');
    }

    public function getDir()
    {
        return Mage::getBaseDir('var') . DS . 'ram' . DS;
    }
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f indexer.php -- [options]

  --stats                       Gets the number of products with the Jeep fitment
  --report                      Gets the products with the Jeep fitment stored in CSV
  --assign --website <web id>   Assigns product with Jeep fitment to the targeted website id
  help                          This help

  <indexer>     Comma separated indexer codes or value "all" for all indexers

USAGE;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();