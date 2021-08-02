<?php
class Growthrocket_Instructionpdf_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getMediaUrl()
    {
        $mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

        return $mediaUrl;
    }

    /**
     * @param $product
     * @return string
     * @throws Zend_Validate_Exception
     */
    public function getInstallationGuidePdf($product)
    {
        $installationPDF = '';
        $validator = new Zend_Validate_File_Exists();
        $validator->addDirectory('docs/');

        $defaultPDf = $product->getData('amp_part_number') . '.pdf';
        $assignedPdf =  $product->getIntallationGuidePdf();

        if(!empty($assignedPdf) && $validator->isValid($assignedPdf)) {
            $installationPDF = $assignedPdf;

        }else if($validator->isValid($defaultPDf)) {
            $installationPDF = $defaultPDf; 
        }


        return $installationPDF;

    }


    /**
     * @param $filename
     * @return string
     */
    public function getGuidePdfUrl($filename)
    {
        return Mage::getBaseUrl() . 'docs/' . $filename;
    }
}
	 