<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/22/17
 * Time: 12:13 AM
 */

class Homebase_Autopart_Block_Ymms_Fitment extends Mage_Core_Block_Template{
    public function __construct(){
        $this->setTemplate('homebase/product/ymms/fitment.phtml');
    }
    public function getFitment(){
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $this->getRequest();

        $parts = explode('/', $_request->getOriginalPathInfo());

        $fitment = ucwords(str_replace('-',' ',$parts[2]));

        $response = array();
        $response[] = explode(' ',$fitment);

        return $response;
    }
}