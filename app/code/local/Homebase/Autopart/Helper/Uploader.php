<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/11/17
 * Time: 2:09 PM
 */

class Homebase_Autopart_Helper_Uploader extends Mage_Core_Helper_Abstract {
    protected $_path;

    const CSV_DIR_PATH ='massimport';
    public function __construct()
    {
        $this->_path = Mage::getBaseDir('media') . DS . 'hautopart';
    }

    public function handleUpload($fieldname ='option_images'){
//        $uploader = Mage::getModel('core/file_uploader', $fieldname);
//        $uploader->skipDbProcessing(true);
        foreach($_FILES as $key => $file){
            if($file['size'] != 0){
                $segments = explode('_', $key);
                $websiteId = array_pop($segments);
                $option_code = array_pop($segments);
                $uploader = new Varien_File_Uploader($file);
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->save($this->_path);
                $savedPath = $uploader->getUploadedFileName();
                $_optImage = Mage::getModel('hautopart/image');
                $_optImage->setOptionId($option_code);
                $_optImage->setWebsiteId($websiteId);
                $_optImage->setImgPath($savedPath);
                $_optImage->save();


                if(!empty($savedPath)){
                    $fullPath = $this->_path . $savedPath;
                    $imagePath = 'hautopart' . dirname($savedPath);
                    $data[] = array(
                        'filename'      => basename($savedPath),
                        'content'       => @file_get_contents($fullPath),
                        'update_time'   => Mage::getSingleton('core/date')->date(),
                        'directory'     => $imagePath
                    );

                    $helper = Mage::helper('core/file_storage');
                    $destinationModel = $helper->getStorageModel(Thai_S3_Model_Core_File_Storage::STORAGE_MEDIA_S3);
                    $destinationModel->importFiles($data);
                }
            }
        }


        $params = $this->_getRequest()->getParams();
        if(array_key_exists('option_delete',$params)){
            $optIds = $this->_getRequest()->getParam('option_delete');
            foreach($optIds as $webId => $opt){
                $optId = array_pop(array_keys($opt));
                $imageCollection = Mage::getModel('hautopart/image')->getCollection();
                $imageCollection->addFieldToFilter('website_id', $webId);
                $imageCollection->addFieldToFilter('option_id', $optId);
                $_image = $imageCollection->fetchItem();
                if($_image && $_image->getId()){
                    $_image->delete();
                }
            }
        }
    }

    public function getPath($filename){
        return  Mage::getBaseUrl('media') . 'hautopart' . $filename;
    }

    public function handleCsvUpload(){
        $path = '';
        if(isset($_FILES['file_csv'])){
            if(array_key_exists('file_csv',$_FILES)){
                $csv = $_FILES['file_csv'];
                if($csv['size'] != 0){
                    $uploader = new Varien_File_Uploader($csv);
                    $uploader->setAllowCreateFolders(true);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($this->_path . DS . self::CSV_DIR_PATH);

                    $path = $uploader->getUploadedFileName();
                }
            }
        }
        return $path;
    }
    public function getCsv($csv){
        return $this->_path . DS . self::CSV_DIR_PATH . $csv;
    }
}