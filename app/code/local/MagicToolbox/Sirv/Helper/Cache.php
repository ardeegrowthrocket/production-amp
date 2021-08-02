<?php

class MagicToolbox_Sirv_Helper_Cache extends Mage_Core_Helper_Abstract
{
    protected $dataHelper = null;

    public function __construct()
    {
        $this->dataHelper = Mage::helper('sirv');
        $this->setCacheTable();
    }

    public function getCacheTable()
    {
        static $cacheTable = null;
        if ($cacheTable === null) {
            $modelsNode = Mage::getConfig()->getNode()->global->models;
            $entityConfig = $modelsNode->sirv_mysql4->entities->cache;
            $cacheTable = (string)$entityConfig->table;

            //NOTE: values for default scope
            $defaultBucket = Mage::getStoreConfig('sirv/s3/bucket', '0');
            $defaultImageFolder = Mage::getStoreConfig('sirv/general/image_folder', '0');
            //NOTE: values for current scope
            $bucket = $this->dataHelper->getStoreConfig('sirv/s3/bucket');
            $imageFolder = $this->dataHelper->getStoreConfig('sirv/general/image_folder');

            if ($defaultBucket != $bucket || $defaultImageFolder != $imageFolder) {
                $cacheTable = $cacheTable . '_' . md5("{$bucket}|{$imageFolder}");
            }
        }
        return $cacheTable;
    }

    public function setCacheTable()
    {
        static $isSet = false;
        if ($isSet) {
            return;
        }

        $modelsNode = Mage::getConfig()->getNode()->global->models;
        $entityConfig = $modelsNode->sirv_mysql4->entities->cache;
        $cacheTable = $this->getCacheTable();
        if ($cacheTable != (string)$entityConfig->table) {
            /** @var Mage_Core_Model_Resource $resource */
            $resource = Mage::getSingleton('core/resource');

            $defaultTable = $resource->getTableName('sirv/cache');
            $entityConfig->table = $cacheTable;
            $table = $resource->getTableName('sirv/cache');

            /** @var Magento_Db_Adapter_Pdo_Mysql $conn */
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            if (!$conn->isTableExists($table)) {
                //NOTE: create table
                $conn->query("CREATE TABLE `{$table}` LIKE `{$defaultTable}`;");
            }
        }
        $isSet = true;
    }

    public function isCached($url, $modificationTime = null)
    {
        try {
            $model = Mage::getModel('sirv/cache')->load($url, 'url');
            $timestamp = $model->getModificationTime();
            if (!$timestamp) {
                //NOTE: URL is not in cache
                return false;
            }
            if ($modificationTime === null) {
                return true;
            }
            $timestamp = (int)$timestamp;
        } catch (Exception $e) {
            return false;
        }

        return $modificationTime <= $timestamp;
    }

    public function updateCache($url, $modificationTime = null, $remove = false)
    {
        try {
            $model = Mage::getModel('sirv/cache')->load($url, 'url');
            if ($remove) {
                $model->delete();
            } else {
                $model->setUrl($url);
                $model->setModificationTime($modificationTime);
                $model->save();
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function getCollection()
    {
        return Mage::getModel('sirv/cache')->getCollection();
    }

    public function clearCache()
    {
        Mage::getModel('sirv/cache')->getCollection()->truncate();
    }
}
