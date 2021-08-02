<?php

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        error_reporting(E_ALL);

        $siteMapCollection =  Mage::getResourceModel('hsitemap/multimap_collection')->addFieldToSelect('*');

        if(!empty($siteMapCollection->getSize())) {
            foreach ($siteMapCollection as $sitemap) {

                $siteMapId = $sitemap->getId();
                $fileName = $sitemap->getFilename();

                echo "Sitemap generation on: {$fileName}" . PHP_EOL;
                try{
                    $_model = Mage::getModel('hsitemap/multimap')->load($siteMapId);
                    $_model->generateXml();

                }catch (Exception $exception) {
                    echo "Sitemap generation Error on: {$fileName}" . PHP_EOL;
                }
            }
        }
    }

}

$shell = new Mage_Shell_Compiler();
$shell->run();