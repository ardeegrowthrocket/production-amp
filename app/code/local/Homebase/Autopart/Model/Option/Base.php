<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 09/03/2017
 * Time: 1:20 AM
 */

class Homebase_Autopart_Model_Option_Base extends Varien_Object{
    /** @var array $_values */
    protected $_values;

    protected $SORT_DIR = 'ASC';

    public function __construct($attribute, $dir = 'ASC'){
        $this->SORT_DIR = $dir;
        /** @var Mage_Eav_Model_Entity_Attribute $_attribute */
        $_attribute = Mage::getModel('eav/entity_attribute')->load($attribute,'attribute_code');

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection $_options */
        $_options = Mage::getModel('eav/entity_attribute_option')
            ->getCollection()
            ->addFilter('attribute_id',$_attribute->getAttributeId());

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        /** @var String $table */
        $table = $resource->getTableName('eav/attribute_option_value');

        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getConnection('core_read');

        /** @var Varien_Db_Statement_Pdo_Mysql $statement */
        $query = 'SELECT value FROM ' . $table . ' WHERE option_id = :id AND store_id = :store';


        $this->_values  = array();
        $unsorted = array();
        /** @var Mage_Eav_Model_Entity_Attribute_Option $_option */
        foreach($_options as $_option){
            $statement = $reader->query($query ,array(
                'id' => $_option->getId(),
                'store'  => 0
            ));
            $result = $statement->fetch();

            if($result['value'] == '1500') {
                $result['value'] = '1500 DS';
            }

            $unsorted[] = array(
                'value' => $_option->getId(),
                'label' => $result['value']
            );

        }
        usort($unsorted,array($this,'sortHelper'));
        $this->_values = $unsorted;
    }
    public function sortHelper($a, $b){
        if($this->SORT_DIR == 'ASC')
            return strcmp($a['label'], $b['label']);
        else
            return strcmp($b['label'], $a['label']);
    }
}