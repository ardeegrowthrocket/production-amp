<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/02/2018
 * Time: 9:17 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    protected $_oldCategoryName;

    protected $_newCategoryName;

    protected $_consolidatedCategory;

    protected $autoType;

    protected $_websiteId = 5;

    protected $_attributeFilter = array();

    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);

        $this->getAutoTypeData();
        $this->_getCustomCategoryFilter();

        $csvPath = Mage::getBaseDir('var') . DS . 'lfp_new_category.csv';

        $csvObject = new Varien_File_Csv();
        $csvData = $csvObject->getData($csvPath);

        foreach ($csvData as $key => $value){

            $oldCategoryLabel = $value[2];
            $newCategoryLabel = $value[1];

            if(isset($this->autoType[strtolower($oldCategoryLabel)]) && isset($this->autoType[strtolower($newCategoryLabel)])){

                $oldCatId = (int) $this->autoType[strtolower($oldCategoryLabel)];
                $newCatId = (int) $this->autoType[strtolower($newCategoryLabel)];

                $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect(array('sku', 'auto_type'))
                    ->addAttributeToFilter('auto_type', array('in' => array($oldCatId)))
                    ->addWebsiteFilter($this->_websiteId);

                if($collection->getSize() > 0) {

                    foreach ($collection as $product) {

                        if(empty($product->getAutoType())){
                            continue;
                        }

                        $newAutoType = array();
                        $autoType = explode(',',$product->getAutoType());
                        foreach ($autoType as $key){
                            if($oldCatId != $key){
                                $newAutoType[] = $key;
                            }
                        }
                        $newAutoType[] = $newCatId;

                        $resource = $product->getResource();
                        $product->setData('auto_type', implode(',',$newAutoType));
                        $resource->saveAttribute($product, 'auto_type');

                        $product->setData('custom_category_filter', $this->_attributeFilter[strtolower($oldCategoryLabel)]);
                        $resource->saveAttribute($product, 'custom_category_filter');

                        echo 'SKU Updated: ' . $product->getSku() . PHP_EOL;
                    }

                }
            }
        }
    }

    /**
     * Customer Category
     */
    public function getAutoTypeData()
    {
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'auto_type');
        $allOptions = $attribute->getSource()->getAllOptions(true, true);
        foreach ($allOptions as $instance) {
            $this->autoType[strtolower($instance['label'])] = $instance['value'];
        }
    }

    /**
     * Custom Attribute Filter
     */
    protected function _getCustomCategoryFilter()
    {

        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'custom_category_filter');
        $allOptions = $attribute->getSource()->getAllOptions(true, true);
        foreach ($allOptions as $instance) {
            $this->_attributeFilter[strtolower($instance['label'])] = $instance['value'];
        }
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();