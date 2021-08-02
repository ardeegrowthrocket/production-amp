<?php

class MagicToolbox_Sirv_Model_Adapter_S3 extends Varien_Object
{
    protected $sirv = null;

    protected $auth = false;

    protected $enabled = false;

    protected $bucket = '';

    protected $baseUrl = '';

    protected $baseDirectUrl = '';

    protected $imageFolder = '';

    protected $cacheHelper = null;

    protected $baseMediaPath = '';

    protected function _construct()
    {
        if ($this->sirv === null) {

            $dataHelper = Mage::helper('sirv');

            $this->bucket = $dataHelper->getStoreConfig('sirv/s3/bucket');

            //$protocol = (Mage::app()->getStore()->isCurrentlySecure() ? 'https' : 'http');
            $this->baseUrl = 'https://' . $this->bucket . (($dataHelper->getStoreConfig('sirv/general/network') == 'CDN') ? '-cdn' : '') . '.sirv.com';

            $customDomain = $dataHelper->getStoreConfig('sirv/general/сustom_domain');
            if (is_string($customDomain)) {
                $customDomain = trim($customDomain);
                //NOTE: cut protocol
                $customDomain = preg_replace('#^(?:[a-zA-Z0-9]+:)?//#', '', $customDomain);
                //NOTE: cut path with query
                $customDomain = preg_replace('#^([^/]+)/.*$#', '$1', $customDomain);
                //NOTE: cut query without path
                $customDomain = preg_replace('#^([^\?]+)\?.*$#', '$1', $customDomain);
                if (!empty($customDomain)) {
                    $this->baseUrl = 'https://' . $customDomain;
                }
            }

            $this->baseDirectUrl = 'https://' . $this->bucket . '.sirv.com';
            $this->imageFolder = '/' . $dataHelper->getStoreConfig('sirv/general/image_folder');
            $this->sirv = Mage::getModel(
                'sirv/adapter_s3_wrapper',
                array(
                    'host' => 's3.sirv.com',
                    'bucket' => $this->bucket,
                    'key' => $dataHelper->getStoreConfig('sirv/s3/key'),
                    'secret' => $dataHelper->getStoreConfig('sirv/s3/secret'),
                )
            );
            $bucketsList = $this->sirv->listBuckets();
            if (!empty($bucketsList)) {
                $this->auth = true;
            }
            if (!empty($this->bucket) && in_array($this->bucket, $bucketsList)) {
                $this->enabled = true;
            }
            $this->cacheHelper = Mage::helper('sirv/cache');
            $this->baseMediaPath = str_replace('\\', '/', Mage::getBaseDir('media'));
        }
    }

    public function isAuth()
    {
        return $this->auth;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getProfiles()
    {
        if ($this->enabled) {
            return $this->sirv->getProfiles();
        }
        return false;
    }

    public function clearCache()
    {
        if (!$this->enabled) {
            return false;
        }

        $collection = $this->cacheHelper->getCollection();
        $collectionSize = $collection->getSize();

        $result = true;

        if ($collectionSize) {
            $pageNumber = 1;
            $pageSize = 100;
            $lastPageNumber = ceil($collectionSize / $pageSize);
            do {
                $collection->setCurPage($pageNumber)->setPageSize($pageSize);
                $urls = array();
                foreach ($collection->getIterator() as $record) {
                    $urls[] = $this->imageFolder . $record->getData('url');
                }
                try {
                    $this->sirv->deleteMultipleObjects($urls);
                } catch (Exception $e) {
                    $result = false;
                }
                $collection->clear();
                $pageNumber++;
            } while ($pageNumber <= $lastPageNumber);

            if ($result) {
                //NOTE: truncate cache table
                $this->cacheHelper->clearCache();
            }
        }

        return $result;
    }

    public function save($destFileName, $srcFileName)
    {
        if (!$this->enabled) {
            return false;
        }
        $destFileName = $this->getRelative($destFileName);
        try {
            $result = $this->sirv->uploadFile($this->imageFolder . $destFileName, $srcFileName, true);
        } catch (Exception $e) {
            $result = false;
        }
        if ($result) {
            $modificationTime = filemtime($srcFileName);
            $this->cacheHelper->updateCache($destFileName, $modificationTime);
        }
        return $result;
    }

    public function remove($fileName)
    {
        if (!$this->enabled) {
            return false;
        }
        $fileName = $this->getRelative($fileName);
        try {
            $result = $this->sirv->deleteObject($this->imageFolder . $fileName);
        } catch (Exception $e) {
            $result = false;
        }
        if ($result) {
            $this->cacheHelper->updateCache($fileName, null, true);
        }
        return $result;
    }

    public function getUrl($fileName)
    {
        return $this->baseUrl . $this->imageFolder . $this->getRelative($fileName);
    }

    public function getDirectUrl($fileName)
    {
        return $this->baseDirectUrl . $this->imageFolder . $this->getRelative($fileName);
    }

    public function getRelUrl($fileName)
    {
        return $this->imageFolder . $this->getRelative($fileName);
    }

    public function fileExists($fileName, $modificationTime)
    {

        $fileName = $this->getRelative($fileName);

        $url = $this->getDirectUrl($fileName) . '?info=' . time();
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_NOBODY, true);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($c);

        //NOTE: return false if error
        if (curl_errno($c)) {
            curl_close($c);
            return false;
        }

        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        //NOTE: for test to see if file size greater than zero
        //$size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($c);

        if ($code == 200/* && $size*/) {
            return true;
        } else {
            return false;
        }

        //NOTE: to check with S3
        // return $this->sirv->doesObjectExist($this->imageFolder . $fileName);
    }

    public function getRelative($fileName)
    {
        $fileName = str_replace('\\', '/', $fileName);
        // if(substr($fileName, 0, 1) != '/') {
        //     $fileName = '/' . $fileName;
        // }
        return str_replace($this->baseMediaPath, '', $fileName);
    }
}
