<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/02/2018
 * Time: 9:17 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{


    function _getConnection($type = 'core_read'){
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    function _getTableName($tableName){
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    function _getEntityTypeId($entity_type_code = 'catalog_product'){
        $connection = $this->_getConnection('core_read');
        $sql		= "SELECT entity_type_id FROM " .$this->_getTableName('eav_entity_type') . " WHERE entity_type_code = ?";
        return $connection->fetchOne($sql, array($entity_type_code));
    }

    function _getAttributeId($attribute_code = 'price'){
        $connection = $this->_getConnection('core_read');
        $sql = "SELECT attribute_id
				FROM " . $this->_getTableName('eav_attribute') . "
			WHERE
				entity_type_id = ?
				AND attribute_code = ?";
        $entity_type_id = $this->_getEntityTypeId();
        return $connection->fetchOne($sql, array($entity_type_id, $attribute_code));
    }

    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);

        $this->_updateMissingImages();

    }

    function _updateMissingImages()
    {
        $connection				= $this->_getConnection('core_read');
        $smallImageId			= $this->_getAttributeId('small_image');
        $imageId				= $this->_getAttributeId('image');
        $thumbnailId			= $this->_getAttributeId('thumbnail');
        $mediaGalleryId			= $this->_getAttributeId('media_gallery');

        //getting small, base, thumbnail images from catalog_product_entity_varchar for a product
        $productVarcharTable = $this->_getTableName('catalog_product_entity_varchar');
        $productMediaGallery = $this->_getTableName('catalog_product_entity_media_gallery');
        $sql	= "SELECT * FROM {$productVarcharTable} as t1 
                    WHERE NOT EXISTS (SELECT 1 FROM {$productMediaGallery} as t2 WHERE t1.entity_id = t2.entity_id)
                    AND t1.attribute_id IN (?, ?, ?) AND t1.value != 'no_selection'";

        $rows	= $connection->fetchAll($sql, array($imageId, $smallImageId, $thumbnailId));
        if(!empty($rows)){
            foreach($rows as $item){
                $productId = $item['entity_id'];
                $image = $item['value'];

                //check if that images exist in catalog_product_entity_media_gallery table or not
                if(!$this->_checkIfRowExists($productId, $mediaGalleryId, $image)){
                    //insert that image in catalog_product_entity_media_gallery if it doesn't exist
                    $this->_insertRow($productId, $mediaGalleryId, $image);
                    $missingImageUpdates = '> Updated:: $productId=' . $productId . ', $image=' . $image;
                    echo $missingImageUpdates . PHP_EOL;
                }
            }
        }
    }

    function _checkIfRowExists($productId, $attributeId, $value){
        $tableName  = $this->_getTableName('catalog_product_entity_media_gallery');
        $connection = $this->_getConnection('core_read');
        $sql		= "SELECT COUNT(*) AS count_no FROM " . $this->_getTableName($tableName) . " WHERE entity_id = ? AND attribute_id = ?  AND value = ?";
        $count		= $connection->fetchOne($sql, array($productId, $attributeId, $value));
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    function _insertRow($productId, $attributeId, $value){
        $connection	= $this->_getConnection('core_write');
        $tableName	= $this->_getTableName('catalog_product_entity_media_gallery');

        $sql = "INSERT INTO " . $tableName . " (attribute_id, entity_id, value) VALUES (?, ?, ?)";
        $connection->query($sql, array($attributeId, $productId, $value));
    }


}

$shell = new Mage_Shell_Compiler();
$shell->run();