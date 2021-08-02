<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 7/6/17
 * Time: 12:34 AM
 */

class Mage_Core_Model_File_Validator_Image
{
    const NAME = "isImage";

    protected $_allowedImageTypes = array(
        IMAGETYPE_JPEG,
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG2000,
        IMAGETYPE_PNG,
        IMAGETYPE_ICO,
        IMAGETYPE_TIFF_II,
        IMAGETYPE_TIFF_MM
    );

    /**
     * Setter for allowed image types
     *
     * @param array $imageFileExtensions
     * @return $this
     */
    public function setAllowedImageTypes(array $imageFileExtensions = array())
    {
        $map = array(
            'tif' => array(IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM),
            'tiff' => array(IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM),
            'jpg' => array(IMAGETYPE_JPEG, IMAGETYPE_JPEG2000),
            'jpe' => array(IMAGETYPE_JPEG, IMAGETYPE_JPEG2000),
            'jpeg' => array(IMAGETYPE_JPEG, IMAGETYPE_JPEG2000),
            'gif' => array(IMAGETYPE_GIF),
            'png' => array(IMAGETYPE_PNG),
            'ico' => array(IMAGETYPE_ICO),
            'apng' => array(IMAGETYPE_PNG)
        );

        $this->_allowedImageTypes = array();

        foreach ($imageFileExtensions as $extension) {
            if (isset($map[$extension])) {
                foreach ($map[$extension] as $imageType) {
                    $this->_allowedImageTypes[$imageType] = $imageType;
                }
            }
        }

        return $this;
    }

    /**
     * Validation callback for checking is file is image
     *
     * @param  string $filePath Path to temporary uploaded file
     * @return null
     * @throws Mage_Core_Exception
     */
    public function validate($filePath)
    {
        list($imageWidth, $imageHeight, $fileType) = getimagesize($filePath);
        if ($fileType) {
            if ($this->isImageType($fileType)) {
                //replace tmp image with re-sampled copy to exclude images with malicious data
                $image = imagecreatefromstring(file_get_contents($filePath));
                if ($image !== false) {
                    $img = imagecreatetruecolor($imageWidth, $imageHeight);
                    switch ($fileType) {
                        case IMAGETYPE_GIF:
                            imagecopyresampled($img, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);
                            imagegif($img, $filePath);
                            break;
                        case IMAGETYPE_JPEG:
                            imagecopyresampled($img, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);
                            imagejpeg($img, $filePath, 100);
                            break;
                        case IMAGETYPE_PNG:
                            imagecolortransparent($img, imagecolorallocatealpha($img, 0, 0, 0, 127));
                            imagealphablending($img, false);
                            imagesavealpha($img, true);
                            imagecopyresampled($img, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);
                            imagepng($img, $filePath);
                            break;
                        default:
                            return;
                    }
                    imagedestroy($img);
                    imagedestroy($image);
                    return null;
                } else {
                    throw Mage::exception('Mage_Core', Mage::helper('core')->__('Invalid image.'));
                }
            }
        }
        throw Mage::exception('Mage_Core', Mage::helper('core')->__('Invalid MIME type.'));
    }

    /**
     * Returns is image by image type
     * @param int $nImageType
     * @return bool
     */
    protected function isImageType($nImageType)
    {
        return in_array($nImageType, $this->_allowedImageTypes);
    }
}
