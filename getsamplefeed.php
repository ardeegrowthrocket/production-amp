<?php
ini_set('memory_limit', '-1');
ini_set('display_errors',1);

require_once 'app/Mage.php';
Mage::app();

$feedDir = Mage::getBaseDir() . DS . 'feed';
$request = Mage::app()->getRequest();
$feedFile = $request->getParam('file');
$limit = 50;

$encrypted_data = md5('feed-secure-download');
if($encrypted_data != $request->getParam('key')){
    die('Not allowed to access this page.');
}

if($feedFile){

    $fullPath = (string) $feedDir . DS . $feedFile;
    $limitRecord = 1;
    $record = array();

    if(!file_exists($fullPath)){
        die('File does not exists');
    }

    $csv = new Varien_File_Csv();
    $csv->setDelimiter("\t");
    $data = $csv->getData($fullPath);

    $record[] = $data[0];
    unset($data[0]);
    $randomDataArray = array_rand($data, $limit);
    foreach ($randomDataArray as $key){
        $record[$key] = $data[$key];
    }

    $sampleFileName  = pathinfo($feedFile, PATHINFO_FILENAME);
    download_csv_results($record, "sample-{$sampleFileName}.csv");
}else{
    die('Not a valid request.');
}


function download_csv_results($results, $name = NULL)
{

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename='. $name);
    header('Pragma: no-cache');
    header("Expires: 0");

    $outstream = fopen("php://output", "wb");

    foreach($results as $result)
    {
        fputcsv($outstream, $result);
    }

    fclose($outstream);
}

?>