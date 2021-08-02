<?php

require_once(Mage::getBaseDir('lib').'/AWS3/autoload.php');
use Aws\S3\S3Client;

class Magecomp_S3Amazon_Model_Observer
{
    public $error = "";
    public function getClient()
    {
        $storeId = Mage::app()->getStore()->getId();
        $accessKey = Mage::getStoreConfig('s3amazon/s3_amazon_options/access_key');
        $secretKey = Mage::getStoreConfig('s3amazon/s3_amazon_options/secret_key');
        $bucket = Mage::getStoreConfig('s3amazon/s3_amazon_options/bucket_key');
        $region = Mage::getStoreConfig('s3amazon/s3_amazon_options/region');

        $this->bucket = $bucket;

        try {
            $client = new S3Client(array(
                        'version'     => 'latest',
                        'credentials' => array(
                            'key'    => $accessKey,
                            'secret' => $secretKey,
                        ),
                        'region'  => $region,
                    ));
            $client->listObjects(array('Bucket' => $bucket));
        } catch(Exception $e) {
            $this->error = $e->getMessage();
            $client = false;
        }
        return $client;
    }

    public function uploadFiles($observer)
    {
        $productId = $observer->getProduct()->getId();
        $type = $observer->getProduct()->getTypeId();
        $savelinkserver = Mage::getStoreConfig('s3amazon/s3_amazon_options/savelinkserver');
        if ($type == 'downloadable' && $savelinkserver == 1) {
            $storeId = Mage::app()->getStore()->getId();
            $accessKey = Mage::getStoreConfig('s3amazon/s3_amazon_options/access_key');
            $secretKey = Mage::getStoreConfig('s3amazon/s3_amazon_options/secret_key');
            $bucket = Mage::getStoreConfig('s3amazon/s3_amazon_options/bucket_key');
            $region = Mage::getStoreConfig('s3amazon/s3_amazon_options/region');
            $_myprodlinks = Mage::getModel('downloadable/link');
            $_myLinksCollection = $_myprodlinks->getCollection()->addProductToFilter($productId);
            if ($client = $this->getClient()) {
                $this->bucket = $bucket;
                foreach ($_myLinksCollection as $_link) {
                    if ($_link->getLinkType() == "file") {
                        try {
                            $title = Mage::getModel("s3amazon/title")->load($_link->getId())->getTitle();
                            //$linkModel = $observer->getLinkmodel();
                            $file = $_link->getLinkFile();//$observer->getFilename();
                            $filePath = Mage::getBaseDir('media')."/downloadable/files/links".$file;
                            $file = explode('/', $file);
                            $filename = str_replace(" ", "", $file[count($file)-1]);
                            $filename = "downloadable/files/links/".$productId."/".$filename;
                            $filename = $this->getValidFileName($client, $filename);
                            // if (!$client->doesObjectExist($this->bucket, $filename)) {
                                $result = $client->putObject(
                                                array(
                                                    'Bucket'       => $this->bucket,
                                                    'Key'          => $filename,
                                                    'SourceFile'   => $filePath,
                                                    'ContentType'  => 'text/plain',
                                                    'StorageClass' => 'REDUCED_REDUNDANCY'
                                                )
                                            );
                            // }
                            $data = array(
                                        'link_file' => '',
                                        'title' => $_link->getTitle(),
                                        'link_url' => $result['ObjectURL'],
                                        'link_type' => "url",
                                    );
                            $_link->addData($data)->setId($_link->getId())->save();
                            $title = Mage::getModel("s3amazon/title")
                                        ->load($_link->getId())
                                        ->addData(array("title" => $title))
                                        ->setId($_link->getId())
                                        ->save();
                            unlink($filePath);
                        } catch (Exception $e) {
                            $e->getMessage();
                        }
                    }
                }
            }
        }
    }

    public function getValidFileName($client, $filename, $count = 1)
    {
        $error = true;
        while ($error) {
            if (!$client->doesObjectExist($this->bucket, $filename)) {
                $error = false;
                return $filename;
            } else {
                $temp = explode(".", $filename);
                $ext = end($temp);
                array_pop($temp);
                $filename = implode(".", $temp);
                if (strpos($filename, "_") !== false) {
                    $tempFile = explode("_", $filename);
                    if (is_numeric(end($tempFile))) {
                        array_pop($tempFile);
                        $filename = implode("_", $tempFile);
                    }
                }
                $filename .= "_".$count;
                $filename = $filename.".".$ext;
                $count++;
                $filename = $this->getValidFileName($client, $filename, $count);
            }
        }
        return $filename;
    }

}
