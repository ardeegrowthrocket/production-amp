<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 08/06/2018
 * Time: 7:39 AM
 */

class Growthrocket_Updater_Model_Csv{
    /**
     * @param $fieldname
     * @return string
     */
    public function getUploadedFilePath($fieldname = 'fileupload'){
        /** @var Mage_Core_Model_File_Uploader $uploader */
        $uploader = Mage::getModel('core/file_uploader', $fieldname);
        $uploader->skipDbProcessing(true);

        $result = $uploader->save($this->getWorkingDir());

        $uploadedFilePath = $result['path'] . $result['file'];

        return $uploadedFilePath;
    }
    public function process($filepath){
        /** @var Mage_ImportExport_Model_Import_Adapter_Csv $importAdapter */
        $importAdapter = Mage_ImportExport_Model_Import_Adapter::findAdapterFor($filepath);
        $ctr = 0;
        /** @var Mage_Catalog_Model_Resource_Product_Action $_productAction */
        $_productAction = Mage::getResourceSingleton('catalog/product_action');
        while($row = $importAdapter->current()){
            $keys = array_keys($row);
            $filteredKeys = array_filter($keys, array($this, 'skipInvalidAttributes'));
            $dataSegment = array_intersect_key($row,array_flip($filteredKeys));
            $filteredDataSegment = array_filter($dataSegment);
            $productId  = $this->getProductId($row['sku']);
            if($productId){
                $_productAction->updateAttributes(array($productId), $filteredDataSegment,1);
            }
            $importAdapter->next();
            $ctr++;
        }
        return $ctr;
    }

    public function getWorkingDir(){
        return Mage::getBaseDir('var') . DS . 'massprice' . DS;
    }

    public function skipInvalidAttributes($var){
        $skips = array('cost','msrp');
        return in_array($var,$skips);
    }

    public function getProductId($sku){

        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readConnection */
        $readConnection = $resource->getConnection('core_read');
        $tableName = $resource->getTableName('catalog_product_entity');
        $select = $readConnection->select()
            ->from($tableName)
            ->where('sku = ?', trim($sku));

        return $readConnection->fetchOne($select);
    }
}