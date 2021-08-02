<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/extension_advr
 * @version   1.2.13
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



class Mirasvit_Advr_Model_System_Config_Source_GeoImportFile
{
    public function toOptionArray()
    {
        $result = array();

        $path = Mage::getSingleton('advr/config')->getGeoFilesPath();
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if (substr($entry, 0, 1) != '.') {
                    $result[] = array(
                        'label' => $entry,
                        'value' => $path . DS . $entry
                    );
                }
            }
            closedir($handle);
        }

        asort($result);

        return $result;
    }
}
