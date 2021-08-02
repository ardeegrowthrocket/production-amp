<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/13/17
 * Time: 9:02 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_products */
        $_products = Mage::getModel('catalog/product')->getCollection();

        /** @var Mage_Catalog_Model_Product $_product */
        foreach($_products as $_product){
            /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
            $writer = $_product->getResource()->getWriteConnection();

            $galleryTable = $_product->getResource()->getTable('catalog/product_attribute_media_gallery');
            $galleryValueTable = $_product->getResource()->getTable('catalog/product_attribute_media_gallery_value');
            $productEntity = $_product->getResource()->getEntityTable();

            $select = $writer->select();

            $select->from(array('i' => $galleryTable),array('value_id','value'));
            $select->join(array('v' => $galleryValueTable),'i.value_id = v.value_id',array('position'));
            $select->where('i.entity_id=?',$_product->getId());

            $results = $writer->fetchAll($select->__toString());


            $hasZeroImgPos = false;
            //Target only those that hasn't been updated manually by checking if the media gallery contains zero
            //order position
            foreach($results as $result){
                if(intval($result['position']) == 0){
                    $hasZeroImgPos = true;
                    break;
                }
            }

            if($hasZeroImgPos){
                $_rproduct = Mage::getModel('catalog/product')->load($_product->getId());
                //Fetch Product's images from oscommerce
                $oscommImgs = $this->fetchoscom($_rproduct->getAmpPartNumber());
                if(count($oscommImgs) > 0){
                    foreach($results as $result){
                        $filename = pathinfo($result['value'],PATHINFO_BASENAME);
                        $oscommPosition = $this->getOscomImgPos($_rproduct->getAmpPartNumber(),$filename);
                        if($oscommPosition> -1){
                            //Update order position based on oscommerce data
                            Mage::log('No match ' . $_product->getSku() . ' >> ' . $filename, null, 'reassignment_ok.log',true);
                            $this->updateImgPosition($result['value_id'],$oscommPosition);
                        }else{
                            Mage::log('No match ' . $_product->getSku() . ' >> ' . $filename, null, 'reassignment_failed.log',true);
                        }
                    }

                }
            }
        }
    }

    public function updateImgPosition($valueId, $position){
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $resource->getConnection('core_write');

        $galleryValueTable = $resource->getTableName('catalog/product_attribute_media_gallery_value');

        $where = $writer->quoteInto('value_id=?',$valueId);

        $writer->update($galleryValueTable,array('position' => $position), $where);

        $this->fetchoscom('sku');

    }

    public function fetchoscom($productSku){
        $path = Mage::getBaseDir('media') . '/aimg/oscommerce_imgpos.csv';
        $imgs = array();
        if(($handle = fopen($path,"r")) !== false){
            while(($data = fgetcsv($handle)) !== false){
                if(strtolower($data[0]) == strtolower($productSku)){
                    $imgs[] = array(
                        'file' => $data[1],
                        'pos'   => $data[2]
                    );
                }
            }
        }
        return $imgs;
    }

    public function getOscomImgPos($sku, $filename){
        $path = Mage::getBaseDir('media') . '/aimg/oscommerce_imgpos.csv';
        $pos = -1;
        if(($handle = fopen($path,"r")) !== false){
            while(($data = fgetcsv($handle)) !== false){
                if(strtolower($data[0]) == strtolower($sku) && str_replace(' ','_',strtolower($data[1])) == strtolower($filename)){
                    $pos = $data[2];
                    break;
                }
            }
        }
        return $pos;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();