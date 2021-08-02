<?php


$servername = "vipinternalsql.cnmpw88thgnt.us-west-1.rds.amazonaws.com";
$username = "vipautogroupdb";
$password = "1-V!pAt0gr()uppAs$";
$dbname = "customtracker";




$conn = new mysqli($servername, $username, $password,$dbname);

$sql = array();
// Check connection
if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);
}


if ($result = $conn -> query("SELECT GROUP_CONCAT(DISTINCT(date) ORDER by date ASC SEPARATOR '@') as dategroup,GROUP_CONCAT(DISTINCT(order_number) ORDER by date ASC SEPARATOR '@') as dategroup_order,date,email,COUNT(DISTINCT(order_number)) as ordertimes FROM products_v2 WHERE email IS NOT NULL OR email!='' GROUP by email HAVING ordertimes >= 2  AND email NOT IN (SELECT keyword FROM linkmap)")) 
{


while($row = mysqli_fetch_assoc($result)) {  
  
  $explodedate = explode("@",$row['dategroup']);
  $explodedateorder = explode("@",$row['dategroup_order']);
  $f1order = $explodedateorder[0];
  $f2order = $explodedateorder[1];
  $d1 = $explodedate[0];
  $d2 = $explodedate[1];
  $now = strtotime($d2);
  $your_date = strtotime($d1);
  $datediff = $now - $your_date;
  $datediffdays = round($datediff / (60 * 60 * 24));


  $dateformatted1 = date("l, M d, Y",strtotime($explodedate['0']));
  $dateformatted2 = date("l, M d, Y",strtotime($explodedate['1']));


   $sql[] = "INSERT INTO reorder SET days_between='$datediffdays',next_order_date='$d2',first_order_date='$d1',first_order_number='$f1order',next_order_number='$f2order',next_order_date_formatted='$dateformatted2',first_order_date_formatted='$dateformatted1',order_number='$f1order'";


   $sql[] = "INSERT INTO reorder SET days_between='$datediffdays',next_order_date='$d2',first_order_date='$d1',first_order_number='$f1order',next_order_number='$f2order',next_order_date_formatted='$dateformatted2',first_order_date_formatted='$dateformatted1',order_number='$f2order'";


}


$sql[]  = "INSERT INTO linkmap SELECT ('') as ida,email FROM products_v2 WHERE email IS NOT NULL OR email!='' GROUP by email HAVING COUNT(DISTINCT(order_number))  >= 2  ON DUPLICATE KEY UPDATE keyword=VALUES(keyword)";



  $result -> free_result();
}


if ($result = $conn -> query("SELECT GROUP_CONCAT(DISTINCT(date) ORDER by date ASC SEPARATOR '@') as dategroup,GROUP_CONCAT(DISTINCT(order_number) ORDER by date ASC SEPARATOR '@') as dategroup_order,date,phone,COUNT(DISTINCT(order_number)) as ordertimes FROM products_v2 WHERE phone IS NOT NULL OR phone!='' GROUP by phone HAVING ordertimes >= 2 AND phone !='' AND phone NOT IN (SELECT keyword FROM linkmap)")) 
{


while($row = mysqli_fetch_assoc($result)) {  
  
  $explodedate = explode("@",$row['dategroup']);
  $explodedateorder = explode("@",$row['dategroup_order']);
  $f1order = $explodedateorder[0];
  $f2order = $explodedateorder[1];
  $d1 = $explodedate[0];
  $d2 = $explodedate[1];
  $now = strtotime($d2);
  $your_date = strtotime($d1);
  $datediff = $now - $your_date;
  $datediffdays = round($datediff / (60 * 60 * 24));


  $dateformatted1 = date("l, M d, Y",strtotime($explodedate['0']));
  $dateformatted2 = date("l, M d, Y",strtotime($explodedate['1']));


   $sql[] = "INSERT INTO reorder SET days_between='$datediffdays',next_order_date='$d2',first_order_date='$d1',first_order_number='$f1order',next_order_number='$f2order',next_order_date_formatted='$dateformatted2',first_order_date_formatted='$dateformatted1',order_number='$f1order'";


   $sql[] = "INSERT INTO reorder SET days_between='$datediffdays',next_order_date='$d2',first_order_date='$d1',first_order_number='$f1order',next_order_number='$f2order',next_order_date_formatted='$dateformatted2',first_order_date_formatted='$dateformatted1',order_number='$f2order'";


}

$sql[]  = "INSERT INTO linkmap SELECT ('') as ida,phone FROM products_v2 WHERE (phone IS NOT NULL OR phone!='') GROUP by phone HAVING COUNT(DISTINCT(order_number))  >= 2 AND phone!='' ON DUPLICATE KEY UPDATE keyword=VALUES(keyword)";

  $result -> free_result();
}












foreach($sql as $q){


  echo $q."\n";



      if ($conn->query($q) === TRUE) {
      echo "New record created successfully \n";
      } else {
      echo "Error: " . $q . "<br>" . $conn->error;
      }

}


/*
INSERT INTO linkmap SELECT ('') as ida,email FROM products_v2 WHERE email IS NOT NULL OR email!='' GROUP by email HAVING COUNT(DISTINCT(order_number))  >= 2  ON DUPLICATE KEY UPDATE keyword=VALUES(keyword)



INSERT INTO linkmap SELECT ('') as ida,phone FROM products_v2 WHERE (phone IS NOT NULL OR phone!='') GROUP by phone HAVING COUNT(DISTINCT(order_number))  >= 2 AND phone!='' ON DUPLICATE KEY UPDATE keyword=VALUES(keyword)
*/



$conn -> close();

?>