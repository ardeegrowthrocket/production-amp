<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/14/17
 * Time: 11:24 PM
 auto_year
 auto_model
 auto_make
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){
        $path = Mage::getBaseDir('var') . DS . 'homebase' . DS . 'fitment_rawarde.csv';
        $csv = new Varien_File_Csv();
        $data = $csv->getData($path);
        #echo 'Clean up previously assigned SOP Fitments.' . "\n";
        #$this->cleanupSOPFitments();

        $resource = Mage::getSingleton('core/resource');

        $table = $resource->getTableName('hautopart/combination_list');

        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getConnection('core_read');

        foreach($data as $item){

            $sku = $item[0];
            if(!empty($sku)){

                $_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

                if($_product && $_product->getId()){
                    $year = $item[1];
                    $yearId = $this->getOptionValueId(strtolower($year),'auto_year');
                    $model = $item[2];
                    $modelId = $this->getOptionValueId(strtolower($model),'auto_model');
                    $make = $item[3];
                    $makeId = $this->getOptionValueId(strtolower($make),'auto_make');

                    var_dump($item);

                    if(!empty($yearId) && !empty($modelId) && !empty($makeId)){
                        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $collection */
                        $collection = Mage::getModel('hautopart/mix')->getCollection();

                        $collection->addFieldToFilter('product_id', $_product->getId());
                        $collection->addFieldToFilter('year', $yearId->getOptionId());
                        $collection->addFieldToFilter('model', $modelId->getOptionId());
                        $collection->addFieldToFilter('make', $makeId->getOptionId());
                        if($collection->count() == 0){
                            // Use native SQL to prevent model observers from executing.
                            $reader->insert($table,array(
                                'product_id' => $_product->getId(),
                                'year'  => $yearId->getOptionId(),
                                'make'  => $makeId->getOptionId(),
                                'model' => $modelId->getOptionId()
                            ));
//                            $combination = Mage::getModel('hautopart/mix');
//                            $combination->setProductId($_product->getId());
//                            $combination->setYear($yearId->getOptionId());
//                            $combination->setModel($modelId->getOptionId());
//                            $combination->setMake($makeId->getOptionId());
//                            $combination->save();
                            echo '.';
                        }
                    }else{
                        Mage::log($item,null,'processfitment2.log',true);
                    }
                }
            }
        }
        echo "Fitment Assignment complete";
    }


    public function cleanupSOPFitments(){
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_collection */
        $_collection = Mage::getModel('catalog/product')->getCollection();
        $_collection->addFieldToFilter('attribute_set_id',9);

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $table = $resource->getTableName('hautopart/combination_list');

        foreach($_collection as $_product){
            /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
            $reader = $resource->getConnection('core_read');
            $result = $reader->delete($table,'product_id='. $_product->getId());
            echo $result . ' >>'  . $_product->getId() . "\n";

            /** @var Homebase_Autopart_Model_Resource_Mix_Collection $fitmentCollection */
//            $fitmentCollection = Mage::getModel('hautopart/mix')->getCollection();
//            $fitmentCollection->addFieldToFilter('product_id', $_product->getId());
//            $fitment = $fitmentCollection->getFirstItem();
//            if($fitment && $fitment->getId()){
//                $fitment->delete();
//            }
        }

    }

    private function getOptionValueId2($str,$attrcode){

        echo "$str == $attrcode \n";
    }

    private function getOptionValueId($str,$attrcode){

        $mainstr = $str;
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        /** @var String $table */
        $table = $resource->getTableName('eav/attribute_option_value');

        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getConnection('core_read');

        /** @var Varien_Db_Statement_Pdo_Mysql $statement */
        $query = 'SELECT * FROM ' . $table . ' WHERE LOWER(value) = :value';

        $statement = $reader->query($query ,array(
            'value' => strtolower($str)
        ));



        $results = $statement->fetchAll();


        var_dump($results);

        if(count($results)==0){
                $attr_model = Mage::getModel('catalog/resource_eav_attribute');
                $attr = $attr_model->loadByCode('catalog_product', $attrcode);
                $attr_id = $attr->getAttributeId();

                $option['attribute_id'] = $attr_id;
                $option['value'][$attrcode.rand()][0] = $mainstr;
                $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
                $setup->addAttributeOption($option); 

                echo "Added $attrcode == $mainstr\n";
                return $this->getOptionValueId($mainstr,$attrcode);

        }




        foreach($results as $result){
            /** @var Mage_Eav_Model_Entity_Attribute_Option $_option */
            $_option = Mage::getModel('eav/entity_attribute_option')->load($result['option_id']);


            /** @var Mage_Eav_Model_Entity_Attribute $_attribute */
            $_attribute = Mage::getModel('eav/entity_attribute')->load($_option->getAttributeId());

            $productTypeId = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();

            if($_attribute->getEntityTypeId() == $productTypeId){
                return $_option;
            }
        }
        return null;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();