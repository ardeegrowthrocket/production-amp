<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/1/17
 * Time: 1:14 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchAll('SELECT website_id FROM `core_website` WHERE website_id!=0');


        $writeConnection = $resource->getConnection('core_write');

        foreach($results as $data){
            $q = "REPLACE INTO auto_combination (year,make,model,store_id) SELECT year,make,model,('{$data['website_id']}') as store FROM `auto_combination_list` WHERE product_id IN (SELECT product_id FROM catalog_product_website WHERE website_id='{$data['website_id']}')";
            $writeConnection->query($q);
        }


        $query = "INSERT INTO auto_combination_list_labels (`option`, `label`, `store_id`) SELECT option_id,value,store_id FROM `eav_attribute_option_value`";

        $writeConnection->query($query);

        //SELECT * FROM `auto_combination_list` WHERE product_id IN (SELECT product_id FROM catalog_product_website)
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();