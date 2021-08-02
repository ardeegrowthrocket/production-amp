<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 23/04/2018
 * Time: 9:37 AM
 */

require_once 'abstract.php';

class Mage_Shell_Typereset extends Mage_Shell_Abstract{

    public function run()
    {
        // TODO: Implement run() method.
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_collection */
        $_collection = Mage::getModel('catalog/product')->getCollection();

        /** @var Mage_Core_Model_Resource $_resource */
        $_resource = Mage::getSingleton('core/resource');

        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_resource->getConnection('core_write');

        $_collection->addFieldToFilter('attribute_set_id',9);
//        $_collection->addAttributeToFilter('sku', 'T3010YS010-B321SFG000');

        echo "Start Reset\n";
        /** @var Homebase_Autopart_Model_Product $collection */
        foreach($_collection as $collection){
            $_reader->update('catalog_product_entity_varchar',array(
                'value' => null
            ), 'attribute_id=251 and entity_id='.$collection->getId());
        }
        echo "Done resetting the auto_types for SOP\n";
    }
}

$shell = new Mage_Shell_Typereset();

$shell->run();