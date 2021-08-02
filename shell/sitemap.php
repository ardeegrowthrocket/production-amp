<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 8/11/17
 * Time: 10:51 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    /**
     * Run script
     *
     */
    public function run(){

        $mediaDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'sitemap';

        $xmlFiles = scandir($mediaDir);

        foreach($xmlFiles as $file){
            $xml = simplexml_load_file($mediaDir . DIRECTORY_SEPARATOR. $file);
            if($xml){
                foreach($xml->getDocNamespaces() as $prefix => $namespace){
                    if(strlen($prefix) ==0){
                        $prefix = "a";
                    }
                    $xml->registerXPathNamespace($prefix,$namespace);
                }
                $elements = $xml->xpath('//a:loc');
                if(count($elements) == 1){
                    $url = $elements[0];
                    $chandle = curl_init($url);
                    curl_setopt($chandle,  CURLOPT_RETURNTRANSFER, TRUE);
                    $response = curl_exec($chandle);
                    $httpCode = curl_getinfo($chandle, CURLINFO_HTTP_CODE);
                    if($httpCode == 404){
                        Zend_Debug::dump($url);
                    }
                }

            }
        }


    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();