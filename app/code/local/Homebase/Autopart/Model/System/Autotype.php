<?php
class Homebase_Autopart_Model_System_AutoType{

    const ATTRIBUTE_AUTOTYPE_ID = 251;
    const DEFAULT_STORE_ID = 0;

    public function toOptionArray(){
        $options = array();
        $reader = $this->_getReader();
        $resource = $this->_getResource();
        $attributeOptionTable = $resource->getTableName('eav/attribute_option');
        $attributeOptionValueTable = $resource->getTableName('eav/attribute_option_value');


        $select = $reader->select()
            ->from(array('o' => $attributeOptionTable),array('option_id', 'attribute_id'))
            ->join(array('v' => $attributeOptionValueTable),'v.option_id = o.option_id',array('value'))
            ->where('o.attribute_id = ?', self::ATTRIBUTE_AUTOTYPE_ID)
            ->where('v.store_id = ?', self::DEFAULT_STORE_ID);

        $stmt = $select->query();

        $results = $stmt->fetchAll();

        foreach($results as $make){
            array_push($options,array(
                'value' => $make['option_id'],
                'label' => $make['value']
            ));
        }
        return $options;
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource(){
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return Magento_Db_Adapter_Pdo_Mysql
     */
    protected function _getReader(){
        $resource = $this->_getResource();
        return $resource->getConnection('core_read');
    }
}