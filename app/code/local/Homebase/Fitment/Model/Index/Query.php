<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 1/3/18
 * Time: 12:55 PM
 */

class Homebase_Fitment_Model_Index_Query extends Mage_Index_Model_Indexer_Abstract{

    const EVENT_MATCH_RESULT_KEY = 'hfitment_query_match_result';

    /** @var Homebase_Fitment_Helper_Attribute  $helper */
    protected $helper;

    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
        ),
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        )
    );


    /**
     * Get Indexer name
     *
     * @return string
     */
    public function getName()
    {
        // TODO: Implement getName() method.
        return Mage::helper('hfitment')->__('Fitment Query Box');
    }
    public function getDescription(){
        return Mage::helper('hfitment')->__('Generates YMM box values');
    }

    /**
     * Register indexer required data inside event object
     *
     * @param   Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        // TODO: Implement _registerEvent() method.
    }

    /**
     * Process event based on event state data
     *
     * @param   Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        // TODO: Implement _processEvent() method.
    }
    public function reindexAll()
    {
        $this->_ymmLabelListing();
        $this->_updateYmmCombination();
    }

    /**
     * Update YMM combination
     */
    protected function _updateYmmCombination()
    {

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $writeConnection = $resource->getConnection('core_write');

        $autoCombinationTable = 'auto_combination';
        $ymmCombinationList = 'auto_combination_list';

        sleep(2);
        $readConnection->truncateTable($autoCombinationTable);

        $select = "SELECT ymm.year as year,ymm.make as make,ymm.model as model,w.website_id as website_id from catalog_product_website w
                    JOIN {$ymmCombinationList} as ymm ON ymm.product_id = w.product_id;";

        $ymmRecord = $readConnection->fetchAll($select);
        if(!empty($ymmRecord)) {
            $sliceRecord = array_chunk($ymmRecord, 4000);

           foreach ($sliceRecord as $key => $list){
               $ymmArray = array();
               foreach ($list as $ymm){

                   if(!empty($ymm['website_id']) && !empty($ymm['year']) && !empty($ymm['make']) && !empty($ymm['model'])) {
                       $websiteId = $ymm['website_id'];
                       $year = $ymm['year'];
                       $make = $ymm['make'];
                       $model = $ymm['model'];

                       $uniqueId = $websiteId . $year . $make . $model;
                       $ymmArray[$uniqueId] = " ({$uniqueId}, {$year}, {$make}, {$model}, {$websiteId} )";
                   }
               }

                if(!empty($ymmArray)){
                    $joinValues = implode(', ', $ymmArray);
                    $insertQuery = "INSERT IGNORE INTO {$autoCombinationTable} (id, year, make, model , store_id)  VALUES {$joinValues}";
                    $writeConnection->query($insertQuery);
                    echo "Running on INDEX: {$key}" . PHP_EOL;
                    sleep(2);
                }

           }
        }
    }


    /**
     * Update Auto Label
     */
    protected function _ymmLabelListing()
    {
        $attributeList = array();
        $attributesIncluded = array('auto_year', 'auto_make', 'auto_model','auto_type');
        $forInsert = array();

        /** get Auto Label record */
        $labelRecord = array();
        $LabelCollection = Mage::getModel('hautopart/label')->getCollection()->addFieldToSelect('option');
        foreach ($LabelCollection as $item){
            $labelRecord[] = $item->getOption();
        }

        foreach ($attributesIncluded as $attribute){
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute);
            $allOptions = $attribute->getSource()->getAllOptions(true, true);
            foreach ($allOptions as $option) {
                if(!empty($option['label'])){
                    $attributeList[$option['value']] = $option['label'];
                    if(!in_array($option['value'], $labelRecord)){ 
                        $forInsert[] = " ({$option['value']}, '{$option['label']}', '{$option['label']}', 0)";
                    }
                }
            }
        }

        if(!empty($forInsert)){
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $joinValues = implode(', ', $forInsert);
            $writeConnection->query("INSERT INTO auto_combination_list_labels (`option`,`label`,`name`,`store_id`) VALUES {$joinValues}");
        }
    }

    public function _getIndexer(){
        return Mage::getResourceSingleton('hfitment/index_query');
    }
}