<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Gr_Cleanorder extends Mage_Shell_Abstract
{
 
    public function run()
    {
  

    $resource = Mage::getSingleton('core/resource');
    $readConnection = $resource->getConnection('core_read');
    $writeConnection = $resource->getConnection('core_write');
    //$readConnection->fetchAll($query);

$date = date("Y-m-d");

$order = Mage::getSingleton('core/resource')->getTableName('sales/order');

$orderitem = Mage::getSingleton('core/resource')->getTableName('sales/order_item');


$order = "sales_flat_order";
$orderitem = "sales_flat_order_item";


$qstore = "SELECT SUM(grand_total) as sum,COUNT(entity_id) as trans,store_id,created_at FROM $order WHERE updated_at LIKE '%$date%' GROUP by store_id";

$qstore_id = "SELECT entity_id FROM $order WHERE updated_at LIKE '%$date%' GROUP by store_id";

//$qstore = "SELECT SUM(grand_total) as sum,COUNT(entity_id) as trans,store_id,created_at FROM $order GROUP by store_id";
//$qstore_id = "SELECT entity_id FROM $order GROUP by store_id";

$prods_query = "SELECT order_id as oid,product_id,sku,name,SUM(qty_invoiced) as qtys,SUM(row_invoiced) as sales,store_id,created_at, 
(SELECT increment_id FROM sales_flat_order WHERE entity_id=order_id) as ordernumber,
(SELECT customer_email FROM sales_flat_order WHERE entity_id=order_id) as cemail,
(SELECT telephone FROM sales_flat_order as bbb JOIN sales_flat_order_address as ccc WHERE bbb.entity_id=oid AND ccc.entity_id=bbb.billing_address_id ) as cphone
FROM sales_flat_order_item WHERE order_id IN ($qstore_id) GROUP by product_id,store_id,order_id";

//FOR HISTORICAL
//$qstore = "SELECT SUM(grand_total) as sum,COUNT(entity_id) as trans,store_id,created_at,DATE_FORMAT(created_at, \"%Y-%D-%M\") as date_formatted FROM sales_flat_order GROUP by store_id,date_formatted";


$catCollection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect(array("name"));
$cat_array = array();
$storeDatas  = array();
$prod_array  = array();



$attribute = Mage::getModel('eav/entity_attribute')
                ->loadByCode('catalog_product', 'auto_type');

$valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($attribute->getData('attribute_id'))
            ->setStoreFilter(0, false);

$preparedManufacturers = array();            
foreach($valuesCollection as $value) {
    $preparedManufacturers[$value->getOptionId()] = $value->getValue();
}   



/*
SELECT email,date FROM(
SELECT date,email,COUNT(*) as ordertimes FROM products_v2 WHERE email IS NOT NULL OR email!='' GROUP by email HAVING ordertimes >= 2
UNION
SELECT date,phone,COUNT(*) as ordertimes FROM products_v2 WHERE phone IS NOT NULL OR phone!='' GROUP by phone HAVING ordertimes >= 2 AND phone!=''

) as ada WHERE date LIKE '%2020%'



SELECT email,date,ordertimes
FROM(
SELECT date,email,COUNT(*) as ordertimes FROM products_v2 WHERE email IS NOT NULL OR email!='' GROUP by email HAVING ordertimes >= 2
UNION
SELECT date,phone,COUNT(*) as ordertimes FROM products_v2 WHERE phone IS NOT NULL OR phone!='' GROUP by phone HAVING ordertimes >= 2 AND phone!=''

) as ada




SELECT email,date,ordertimes
FROM(
SELECT date,email,COUNT(DISTINCT(date)) as ordertimes FROM products_v2 WHERE email IS NOT NULL OR email!='' GROUP by email HAVING ordertimes >= 2
UNION
SELECT date,phone,COUNT(DISTINCT(date)) as ordertimes FROM products_v2 WHERE phone IS NOT NULL OR phone!='' GROUP by phone HAVING ordertimes >= 2 AND phone!=''

) as ada ORDER by email 






SELECT * FROM (SELECT order_number,date,phone as datalink,COUNT(DISTINCT(date)) as ordertimes FROM products_v2 GROUP by datalink  HAVING ordertimes >=2 AND datalink!=''
UNION
SELECT order_number,date,email as datalink,COUNT(DISTINCT(date)) as ordertimes FROM products_v2 GROUP by datalink  HAVING ordertimes >=2 AND datalink!='') as asd WHERE datalink ='(513)615-4443'
*/



$mocks = array();


$mocks[1]  = "AllMoparParts";
$mocks[3] = "AllMoparParts";
$mocks[5] = "JeepsAreUs";
$mocks[6] = "RamsAreUs";
$mocks[4] = "SubaruPartsPros";
$mocks[7] = "LevittownFordParts";
$mocks[8] = "MoparGenuineParts";
$mocks[9] = "SubaruOnlineParts";


foreach (Mage::app()->getWebsites() as $website) {
    foreach ($website->getGroups() as $group) {
        $stores = $group->getStores();
        foreach ($stores as $store) {
            $storeDatas[$store->getId()] = $mocks[$store->getId()];
        }
    }
}




$date_insert = date("l, M d, Y");

$orderdata = $readConnection->fetchAll($qstore);

$order_result = array();
foreach($orderdata as $data){

    $data['sum'] = round($data['sum'],2);

    $data_result = array();

    $data_result['date_formatted'] = date("l, M d, Y",strtotime($data['created_at']));
    $data_result['site'] = $storeDatas[$data['store_id']];
    $data_result['revenue'] = $data['sum'];
    $data_result['transaction'] = $data['trans'];
    $data_result['date'] = $data['created_at'];


    $order_result[] = $data_result;


}


$date_insert = date("l, M d, Y");

$orderdata = $readConnection->fetchAll($prods_query);

$prod_result = array();
foreach($orderdata as $data){

    $data['qtys'] = round($data['qtys'],2);
    $data['sales'] = round($data['sales'],2);

    $data_result = array();

    $data_result['date_formatted'] = date("l, M d, Y",strtotime($data['created_at']));
    $data_result['product'] = $data['sku']." - ".$data['name'];
    $data_result['revenue'] = $data['sales'];
    $data_result['quantity'] = $data['qtys'];
    $data_result['store'] = $storeDatas[$data['store_id']];
    $data_result['date'] = $data['created_at'];
    $data_result['order_number'] = $data['ordernumber'];
    $data_result['email'] = $data['cemail'];
    $data_result['phone'] = $data['cphone'];
    $data_result['pid'] = $data['product_id'];

    $cats = array();
    $catsfinale = '';



    $data_result['product_category'] = Mage::getResourceModel('catalog/product')->getAttributeRawValue($data['product_id'], 'auto_type', $data['store_id'] );


    if(!empty($data_result['product_category'])){

      $arraycats = array();


      foreach(explode(",",$data_result['product_category']) as $dataatype){


        $arraycats[] = $preparedManufacturers[$dataatype];

        //$arraycats[] = $dataatype;


      }

      $data_result['product_category'] = implode("|", $arraycats);

    }






    $prod_result[] = $data_result;


}






#var_dump($prod_result);
#var_dump($order_result);


$sql = array();

foreach($prod_result as $added){

       $insert = array();
       foreach($added as $k=>$v){

            $v = addslashes($v);
            $insert[] = "$k='$v'";


       }

       $sqldirect = $sql[] = "INSERT INTO products_v2 SET ".implode(",", $insert)." ON DUPLICATE KEY UPDATE ".implode(",", $insert);


}

foreach($order_result as $added){

       $insert = array();
       foreach($added as $k=>$v){

            $v = addslashes($v);
            $insert[] = "$k='$v'";


       }

       $sqldirect = $sql[] = "INSERT INTO revenue_v2 SET ".implode(",", $insert)." ON DUPLICATE KEY UPDATE ".implode(",", $insert);


}


$servername = "vipinternalsql.cnmpw88thgnt.us-west-1.rds.amazonaws.com";
$username = "vipautogroupdb";
$password = "1-V!pAt0gr()uppAs$";
$dbname = "customtracker";




$conn = new mysqli($servername, $username, $password,$dbname);

// Check connection
if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);
}


foreach($sql as $q){


  echo $q."\n";



      if ($conn->query($q) === TRUE) {
      echo "New record created successfully \n";
      } else {
      echo "Error: " . $q . "<br>" . $conn->error;
      }

}







    }
}

$shell = new Gr_Cleanorder();
$shell->run();
