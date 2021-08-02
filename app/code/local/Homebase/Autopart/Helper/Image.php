<?php class Homebase_Autopart_Helper_Image extends Mage_Core_Helper_Abstract
{

    public function reSize($imagePath, $width, $height)
    {
        $imageUrl =  Mage::getBaseDir('media') .DS. 'hautopart' . $imagePath;
        if (! is_file ( $imageUrl ))
            return false;

        $imageResized = Mage::getBaseDir ( 'media' ) . DS . "catalog" . DS . "product" . DS . "cache" .DS. "category" .DS. "resized" . $imagePath;
        if (! file_exists($imageResized) && file_exists($imageUrl) || file_exists($imageUrl) && filemtime($imageUrl) > filemtime($imageResized)) {
            $imageObj = new Varien_Image($imageUrl);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(false);
            $imageObj->quality(100);
            $imageObj->resize($width, $height); 
            $imageObj->save($imageResized);
        }

        if(file_exists($imageResized)){
            return Mage::getBaseUrl('media') ."catalog/product/cache/category/resized" . $imagePath;
        }else{
            return $imageUrl;
        }
    }
}