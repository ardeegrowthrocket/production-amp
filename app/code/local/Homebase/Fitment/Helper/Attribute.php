<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/8/17
 * Time: 3:29 PM
 */

class Homebase_Fitment_Helper_Attribute extends Mage_Core_Helper_Abstract {

    protected $targetAttributes = array(
        'auto_make',
        'auto_model',
        'auto_type',
        'auto_year'
    );

    /**
     * @param $event
     * @return bool
     */
    public function hasUpdatedMake($event){
        $result = $this->getUpdatedLabels($event);
        if(!is_array($result)){
            return false;
        }else{
            return count($result) > 0;
        }
    }
    /**
     * @param $event Mage_Index_Model_Event
     */
    public function getUpdatedLabels($event){
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $dataObject */
        $dataObject = $event->getDataObject();

        if(!($dataObject instanceof Mage_Catalog_Model_Resource_Eav_Attribute)){
            return false;
        }
        $options = $dataObject->getOption();
        if(!is_array($options)){
            return false;
        }
        $values = $options['value'];
        $labelRefTable = $event->getResource()->getTable('hautopart/combination_label');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $event->getResource()->getReadConnection();
        $updatedLabels = array();
        foreach($values as $optionId => $labels){
            $query = $reader->select()
                ->from(array('p' => 'auto_combination_list_labels'))
                ->where('p.option = ?',$optionId)
                ->limit(1);
            /** @var Varien_Db_Statement_Pdo_Mysql $result */
            $result = $reader->query($query);
            if($result->rowCount()) {
                $references = $result->fetchAll();
                foreach ($references as $resultRow) {
                    if (strcmp(strtolower($labels[0]), strtolower($resultRow['label'])) !== 0) {
                        //Update labels
                        $updatedLabels[] = array(
                            'option_id' => $optionId,
                            'label' => $labels[0] //use admin value
                        );
                    }
                }
            }
        }
        if(count($updatedLabels) > 0){
//            foreach ($updatedLabels as $label){
//                /** @var Homebase_Autopart_Model_Resource_Label_Collection $_collection */
//                $_collection = Mage::getModel('hautopart/label')->getCollection();
//                $_collection->addFieldToFilter('option',$label['option_id']);
//                $labelObject = $_collection->fetchItem();
//                $labelObject->setLabel($label['label']);
//                $labelObject->save();
//            }
            return $updatedLabels;
        }else{
            return false;
        }
    }

    public function allowIndexRegister($event){
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $dataObject */
        $dataObject = $event->getDataObject();
        if(!($dataObject instanceof Mage_Catalog_Model_Resource_Eav_Attribute)){
            return false;
        }
        return in_array($dataObject->getAttributeCode(), $this->targetAttributes);
    }
}