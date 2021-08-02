<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/29/17
 * Time: 11:18 AM
 */

class Homebase_Auto_Helper_Path extends Mage_Core_Helper_Data {

    /** @var Mage_Core_Model_Resource $_resource */
    protected $_resource;

    /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
    protected $_reader;

    protected $attributeMap = array();
    public function __construct()
    {
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_reader = $this->_resource->getConnection('core_read');
        $this->attributeMap = array(
            'year'  => 'auto_year',
            'make'  => 'auto_make',
            'model' => 'auto_model',
            'category'   => 'auto_type',
            'part'      => 'part_name'
        );
    }

    public function getOptionText($label, $optionId)
    {
        $url = "";
        if(!empty($optionId)){
            $value = Mage::helper('hautopart/parser')->getLabel($optionId);
            $url = $this->filterTextToUrl($value);
        }

        return $url;
    }

    /**
     * @param $label
     * @param $optionId
     * @return string
     */
    public function getRawOptionText($label, $optionId)
    {
        $value = "";
        if(!empty($optionId)){
            $value = Mage::helper('hautopart/parser')->getLabel($optionId);
        }

        return $value;
    }

    /**
     * @param String $text
     */
    public function filterTextToUrl($text){
        $parts = explode(' ', $text);
        //Remove multiple spaces
        $parts = array_filter($parts);
        //Replace ampersands with 'AND'
        //Replace backslash with 'AND'
        //Replace dash with ''
        $conditions = array(
            array(
                'needle'    => '&',
                'replace'   => 'and'
            ),
            array(
                'needle'    => '/',
                'replace'   => 'and'
            ),
            array(
                'needle'    => '-',
                'replace'   => ''
            ),
            array(
                'needle'    => ',',
                'replace'   => ''
            )
        );
        foreach($conditions as $condition){
            foreach($parts as $ndx=>$part) {
                $parts[$ndx] = strtolower(str_replace($condition['needle'], $condition['replace'], $part));
            }
        }
        $parts = array_filter($parts);
        $url = implode('-',$parts);
        return $url;
    }

    public function replaceCommaWithDash($text){

        return str_replace(',','', $text);
    }

    public function generateLink($stringPath,$controller){
        return Mage::getBaseUrl() . $controller . '/' . $stringPath . '.html';
    }

    /**
     * @param $filePath
     * @return bool
     */
    public function checkFileExist($filePath)
    {
        $headers = get_headers($filePath);
        $response =  stripos($headers[0],"200 OK")? true : false;

        return $response;
    }
}