<?php 

$format = $_SERVER['MAGE_RUN_CODE'];

if(empty($format)){
	$format = "amp";
}
$sitemap = getcwd()."/".$format.".xml";
header('Content-Type: text/xml');
header("Content-Length: ". filesize($sitemap));
readfile($sitemap);
?>