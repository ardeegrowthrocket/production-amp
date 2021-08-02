<?php

class MagicToolbox_Sirv_Model_Varien_Image_Adapter_Gd2 extends Varien_Image_Adapter_Gd2
{
    protected $isSirvEnabled = false;

    protected $sirvAdapter = null;

    public function __construct()
    {
        parent::__construct();
        $dataHelper = Mage::helper('sirv');
        $this->isSirvEnabled = $dataHelper->isSirvEnabled();
        $this->sirvAdapter = Mage::getSingleton('sirv/adapter_s3');
    }

    public function save($destination = null, $newName = null)
    {
        if (!$this->isSirvEnabled) {
            return parent::save($destination, $newName);
        }

        $tempFileName = tempnam(sys_get_temp_dir(), 'sirv');
        parent::save($tempFileName);

        $fileName = isset($destination) ? $destination : $this->_fileName;
        if (isset($destination) && isset($newName)) {
            $fileName = "{$destination}/{$fileName}";
        } elseif (isset($destination) && !isset($newName)) {
            $info = pathinfo($destination);
            $fileName = $destination;
            $destination = $info['dirname'];
        } elseif (!isset($destination) && isset($newName)) {
            $fileName = "{$this->_fileSrcPath}/{$newName}";
        } else {
            $fileName = $this->_fileSrcPath . $this->_fileSrcName;
        }

        if ($this->sirvAdapter->save($fileName, $tempFileName)) {
            @unlink($tempFileName);
        } else {
            $destinationDir = isset($destination) ? $destination : $this->_fileSrcPath;
            if (!is_writable($destinationDir)) {
                try {
                    $io = new Varien_Io_File();
                    $io->mkdir($destination);
                } catch (Exception $e) {
                    throw new Exception("Unable to write file into directory '{$destinationDir}'. Access forbidden.");
                }
            }
            @rename($tempFileName, $fileName);
            @chmod($fileName, 0644);
        }
    }
}
