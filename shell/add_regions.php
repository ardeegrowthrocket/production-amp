<?php

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    public function run()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $readConnection  =  $resource->getConnection('core_read');


        $regions = array(
            "AGU" => "Aguascalientes",
            "BCN" => "Baja California",
            "BCS" => "Baja California Sur",
            "CAM" => "Campeche",
            "CHP" => "Chiapas",
            "CHH" => "Chihuahua",
            "COA" => "Coahuila",
            "COL" => "Colima",
            "DIF" => "Distrito Federal",
            "DUR" => "Durango",
            "GUA" => "Guanajuato",
            "GRO" => "Guerrero",
            "HID" => "Hidalgo",
            "JAL" => "Jalisco",
            "MEX" => "Mexico",
            "MIC" => "Michoacin",
            "MOR" => "Morelos",
            "NAY" => "Nayarit",
            "NLE" => "Nuevo Leon",
            "OAX" => "Oaxaca",
            "PUE" => "Puebla",
            "QUE" => "Queretaro",
            "ROO" => "Quintana Roo",
            "SLP" => "San Luis Potosi",
            "SIN" => "Sinaloa",
            "SON" => "Sonora",
            "TAB" => "Tabasco",
            "TAM" => "Tamaulipas",
            "TLA" => "Tlaxcala",
            "VER" => "Veracruz",
            "YUC" => "Yucatan",
            "ZAC" => "Zacatecas"
        );
        $mexicoCode = "MX";
        foreach ($regions as $code => $name){

            $results = $readConnection->fetchAll("SELECT * FROM directory_country_region WHERE country_id = '{$mexicoCode}' AND code = '{$code}'");

            if(empty($results)){
                $insertCountryRegion = "INSERT INTO `directory_country_region` (`country_id`, `code`, `default_name`) VALUES ('{$mexicoCode}', '{$code}', '{$name}')";
                $writeConnection->query($insertCountryRegion);

                $regionId = $writeConnection->lastInsertId();
                if(!empty($regionId)){
                    $insertRegionName = "INSERT INTO `directory_country_region_name` (`locale`, `region_id`, `name`) VALUES ('en_US', '{$regionId}', '{$name}')";
                    $writeConnection->query($insertRegionName);

                    echo 'New entry: ' . $name . PHP_EOL;
                }
            }

        }
    }

}
$shell = new Mage_Shell_Compiler();
$shell->run();