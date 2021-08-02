<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/17
 * Time: 9:47 PM
 */

class Homebase_Fitment_Model_Index_Route extends Mage_Index_Model_Indexer_Abstract{

    const EVENT_MATCH_RESULT_KEY = 'hfitment_route_match_result';
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

    public function __construct(){
        $this->helper = Mage::helper('hfitment/attribute');
    }

    /**
     * Get Indexer name
     *
     * @return string
     */
    public function getName()
    {
        // TODO: Implement getName() method.
        return Mage::helper('hfitment')->__('Fitment Route');
    }

    public function getDescription(){
        return Mage::helper('hfitment')->__('Generates Routing for fitment routes');
    }
    public function matchEvent(Mage_Index_Model_Event $event){
        Mage::log($event, null, 'matchEvent.log',true);
    }
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        // TODO: Implement _registerEvent() method.
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY,true);
        Mage::log($event, null, 'event.log',true);
        switch($event->getEntity()){
            case Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY:
                if($this->helper->allowIndexRegister($event)){
                    $this->_registerCatalogAttributeEvent($event);
                }
                break;
            case Mage_Catalog_Model_Product::ENTITY:
                $this->_registerProductAttributeEvent($event);
                break;
        }
        return $this;
    }
    protected function _registerProductAttributeEvent(Mage_Index_Model_Event $event){
//        Mage::log($event, null, 'event.log',true);

    }
    protected function _registerCatalogAttributeEvent(Mage_Index_Model_Event $event){
//        Mage::log($event, null, 'event.log',true);
        switch($event->getType()){
            case Mage_Index_Model_Event::TYPE_SAVE:
                $helper = Mage::helper('hfitment/attribute');
                if($helper->hasUpdatedMake($event)) {
                }
//                $event->addNewData('product_fitment_affected_combination',$event);
                break;
        }
        return $this;
    }
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        // TODO: Implement _processEvent() method.
        return $this;
    }
    public function reindexAll()
    {
        $this->_getIndexer()->reindexAll();
    }
    protected function _getIndexer(){
        return Mage::getResourceSingleton('hfitment/index_route');
    }
}