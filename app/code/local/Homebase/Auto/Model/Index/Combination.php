<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/22/17
 * Time: 11:42 AM
 */

class Homebase_Auto_Model_Index_Combination extends Mage_Index_Model_Indexer_Abstract{

    const EVENT_MATCH_RESULT_KEY = 'hauto_combination_match_result';

    protected $_autoTypeLabels = array();

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
        return Mage::helper('hauto')->__('Autopart URL');
    }

    public function getDescription(){
        return Mage::helper('hauto')->__('Rebuild Autopart URL');
    }
    /**
     * Register indexer required data inside event object
     *
     * @param   Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        // TODO: Implement _registerEvent() method.

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY,true);
        Mage::log(':: Register Event ::', null, 'combination.log');
        switch($event->getEntity()){
            case Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY :
                $this->_registerAttributeEvent($event);
                break;
//            case Mage_Catalog_Model_Product::ENTITY :
//                $this->_registerProductEvent($event);
//                break;
        }
        return $this;
    }
    private function _registerAttributeEvent(Mage_Index_Model_Event $event){
        switch($event->getType()){
            case Mage_Index_Model_Event::TYPE_SAVE :
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $_object */
                $_object = $event->getDataObject();
//                Mage::log( $_object->dataHasChangedFor('option'),null, 'combination.log');
//                if($_object->usesSource()){
//                    $options = $_object->getSource()->getAllOptions();
//                    Mage::log($options, null, 'combination.log');
//                }
                break;
        }
    }

    private function _registerProductEvent(Mage_Index_Model_Event $event){
        switch($event->getType()){
            case Mage_Index_Model_Event::TYPE_SAVE :
                Mage::log($event->getDataObject(),null,'combination.log');
                break;
        }
    }
    /**
     * Process event based on event state data
     *
     * @param   Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        // TODO: Implement _processEvent() method.
        Mage::log('::Process Event::', null, 'combination.log');
        Mage::log($event->getNewData(), null, 'combination.log');
        return $this;
    }

    /**
     * @return Homebase_Auto_Model_Resource_Index_Combination
     */
    protected function _getIndexer(){
        return Mage::getResourceSingleton('hauto/index_combination');
    }

    /**
     * @param string $type
     * @return mixed
     */
    function _getConnection($type = 'core_read')
    {
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    /**
     * @param $tableName
     * @return mixed
     */
    function _getTableName($tableName)
    {
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    /**
     * @param string $entity_type_code
     * @return mixed
     */
    function _getEntityTypeId($entity_type_code = 'catalog_product')
    {
        $connection = $this->_getConnection('core_read');
        $sql		= "SELECT entity_type_id FROM " .$this->_getTableName('eav_entity_type') . " WHERE entity_type_code = ?";
        return $connection->fetchOne($sql, array($entity_type_code));
    }

    /**
     * @param string $attribute_code
     * @return mixed
     */
    function _getAttributeId($attribute_code = 'price')
    {
        $connection = $this->_getConnection('core_read');
        $sql = "SELECT attribute_id FROM " . $this->_getTableName('eav_attribute') . " WHERE entity_type_id = ? AND attribute_code = ?";
        $entity_type_id = $this->_getEntityTypeId();
        return $connection->fetchOne($sql, array($entity_type_id, $attribute_code));
    }

    protected function _getAutoTypeLabel($optionId)
    {
        $label = "";
        if(!$this->_autoTypeLabels){
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'auto_type');
            $allOptions = $attribute->getSource()->getAllOptions(true, true);
            foreach ($allOptions as $instance) {
                $this->_autoTypeLabels[$instance['value']] = $instance['label'];
            }
        }

        if(isset($this->_autoTypeLabels[$optionId])){
            $label = $this->_autoTypeLabels[$optionId];
        }

        return $label;
    }

    protected function _getWriteAdapter()
    {
        return $this->_getConnection('write');
    }

    protected function _createTable()
    {
        $writer = $this->_getWriteAdapter();
        $tableName = 'auto_part_listing';

        if($writer->isTableExists($tableName)){
            return;
        }

        $table = $this->_getWriteAdapter()
            ->newTable($tableName)
            ->setComment('YMM Part Name');
        if($this->_columnsSql === null){
            $table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true
            ),' Id')
                ->addColumn('identifier', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ))
                ->addColumn('year',Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(
                    'nullable'  => false,
                ))
                ->addColumn('make',Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(
                    'nullable'  => false,
                ))
                ->addColumn('model',Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(
                    'nullable'  => false,
                ))
                ->addColumn('website_id',Varien_Db_Ddl_Table::TYPE_INTEGER, 3, array(
                    'nullable'  => false,
                ))
                ->addColumn('category', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ))
                ->addColumn('part_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ))
                ->addColumn('var', Varien_Db_Ddl_Table::TYPE_TEXT, 500, array(
                    'nullable'  => false,
                ))
                ->addColumn('updated_at',Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(
                    'nullable'  => false,
                ))
                ->addIndex($writer->getIndexName($tableName,array('identifier')),array('identifier'),array('type' => 'unique'));
        }
        $writer->createTable($table);
    }

    public function reindexAll()
    {
        /** Create table if not exist */
        $this->_createTable();

        $eavConn = Mage::getResourceModel('core/config');
        $conn = Mage::getSingleton('core/resource');
        $reader = $conn->getConnection('core_read');
        $timeStamp = time();

        $catalogWebTable = $conn->getTableName('catalog/product_website');
        $catalogProductTable = $eavConn->getTable('catalog/product');
        $statusAttrTable = $eavConn->getValueTable('catalog/product','int');
        $varAttrTable = $eavConn->getValueTable('catalog/product','varchar');
        $fitmentTable = $conn->getTableName('hautopart/combination_list');
        $urlHelper = Mage::helper('hfitment/url');

         $partNameAttribute = $this->_getAttributeId('part_name');
         $autoTypeAttribute = $this->_getAttributeId('auto_type');
         $productStatus = $this->_getAttributeId('status');
         $image = $this->_getAttributeId('image');

        foreach (Mage::app()->getWebsites() as $website) {

            $websiteName = $website->getName();
            $websiteId = $website->getId();
            
            $this->_createCustomAttributeTable($websiteId, $varAttrTable, $partNameAttribute, $autoTypeAttribute, $image);

            $productFields = sprintf('product_fields_%s', $websiteId);
            $query = $reader->select()->from(array('w' => $catalogWebTable))
            ->join(array('entity' => $catalogProductTable), 'entity.entity_id=w.product_id', array('sku'))
            ->join(array('s' => $statusAttrTable), 's.entity_id=w.product_id', array('status' => 'value'))
            ->join(array('product_fields' => $productFields), 'product_fields.product_id=w.product_id', array('part_name' => 'part_name', 'auto_type' => 'auto_type', 'image' => 'image'))
            ->join(array('fitment' => $fitmentTable), 'fitment.product_id=w.product_id', array('year' => 'year', 'make' => 'make', 'model' => 'model'))
            ->where('s.attribute_id=?', $productStatus)
            ->where('s.value=?', 1)
            ->where('w.website_id=?', $websiteId)
            ->group(array('entity.entity_id', 'year', 'make', 'model'))
            ->query();

            $results = $query->fetchAll();

            $pageSize = count($results);
            if($pageSize > 0) {
                $splitResult = array_chunk($results, 1000);
                $pageNum = 0;
                foreach ($splitResult as $key => $item){
                    $values = array();
                    $pageNum += 1;
                    echo "{$websiteName} - Current Page: $pageNum" . PHP_EOL;
                    sleep(2);
                    foreach ($item as $row){

                        $yearId = $row['year'];
                        $makeId = $row['make'];
                        $modelId = $row['model'];
                        $partName = $row['part_name'];
                        $autoTypeIds = $row['auto_type'];

                        $autoTypeArray = explode(',', $autoTypeIds);

                        if (count($autoTypeArray) > 0) {

                            foreach ($autoTypeArray as $autoType) {
                                $autoTypeLabel = $this->_getAutoTypeLabel($autoType);
                                if (empty($partName) || empty($autoTypeLabel)) {
                                    continue;
                                }

                                //$row['image'] =  $this->_resizeImage($row['image'],$websiteId);
                                $var = array(
                                    'image' => $row['image'],
                                    'category_id' => $autoType
                                );

                                $identifier = implode('-',array($yearId,$makeId,$modelId,$websiteId,$autoType,$partName));
                                $var = serialize($var);
                                $values[] = "('{$identifier}',{$yearId},{$makeId},{$modelId},{$websiteId},'{$autoTypeLabel}','{$partName}','{$var}',{$timeStamp})";
                            }

                        }
                    }

                    if(!empty($values)){
                        $combineValues = implode(',', $values);
                         try {
                            $urlHelper->_query("INSERT INTO auto_part_listing (identifier,year,make,model,website_id,category,part_name,var,updated_at)
                                        VALUES {$combineValues} ON DUPLICATE KEY UPDATE year = values(year),make = values(make), model = values(model),category = values(category),
                                        part_name = values(part_name), var = values(var), updated_At = $timeStamp;
                                        ");

                        } catch (Exception $e) {
                            Mage::log($e->getMessage(), null, 'auto_part_reindex.log');
                        }
                    }
                }
            }
            $this->_removeOldData($timeStamp, $websiteId);
        }
    }

    protected function _resizeImage($image,$websiteId)
    {
        $resizedImagePath = $image;
        $folderPath = Mage::getBaseDir('media') . DS . "ymm-images" . DS . $websiteId;
        $io = new Varien_Io_File();
        if (!$io->fileExists($folderPath, false)) {
            $io->mkdir($folderPath);
        }

        $_imageUrl = Mage::getModel('catalog/product_media_config')->getMediaPath($image);
        $imageResized =  $folderPath . DS . $image;

        $isImageResizedExist = file_exists($imageResized);
        if (!$isImageResizedExist && file_exists($_imageUrl) && $this->_isValidImageFormat($_imageUrl)) {
            $imageObj = new Varien_Image($_imageUrl);
            $imageObj->constrainOnly(TRUE);
            $imageObj->keepAspectRatio(TRUE);
            $imageObj->keepFrame(FALSE);
            $imageObj->resize(300, 300);
            $imageObj->save($imageResized);

            $resizedImagePath = $image;
        }

        return $resizedImagePath;
    }

    /**
     * @param $image
     * @return false|int
     */
    protected function _isValidImageFormat($image)
    {
            if(empty($image['mime'])){
                return false;
            }
        return preg_match('/(\.jpg|\.png|\.gif)$/i', $image);
    }

    /**
     * Delete Old Record;
     * @param $timeStamp
     */
    protected function _removeOldData($timeStamp, $websiteId)
    {
        if(!empty($timeStamp)){
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $deleteQuery = "DELETE FROM auto_part_listing WHERE updated_at < {$timeStamp} AND website_id = {$websiteId}";
            $conn->query($deleteQuery);
        }
    }

    /**
     * @param $websiteId
     * @param $varAttrTable
     * @param $partName
     * @param $autoType
     */
    protected function _createCustomAttributeTable($websiteId, $varAttrTable, $partName, $autoType, $image)
    {
        try{
            $conn = Mage::getSingleton('core/resource');
            $writer = $conn->getConnection('core_write');
            $tablename = "product_fields_{$websiteId}";

            $table = $conn->getConnection('core_write')->newTable($tablename);
            $table->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER,array(
                'unsigned' => true,
                'nullable' => false,
            ))
                ->addColumn('auto_Type', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
                ->addColumn('part_name', Varien_Db_Ddl_Table::TYPE_VARCHAR,null)
                ->addColumn('image', Varien_Db_Ddl_Table::TYPE_VARCHAR,null);

            $writer->createTemporaryTable($table);

            $select = "SELECT distinct(w.product_id) as product_id,c.value as auto_type, d.value as part_name,e.value as image FROM catalog_product_website as w
                LEFT JOIN {$varAttrTable} as c ON w.product_id = c.entity_id and c.attribute_id={$autoType}
                LEFT JOIN {$varAttrTable} as d ON w.product_id = d.entity_id and d.attribute_id={$partName}
                LEFT JOIN {$varAttrTable} as e ON w.product_id = e.entity_id and e.attribute_id={$image}
                WHERE w.website_id = {$websiteId} ORDER BY 1";

            $results = $writer->fetchAll($select);
            $writer->insertArray($tablename,array('product_id','auto_type','part_name','image'),$results);
        }catch (Exception $e){
            Mage::log($e->getMessage(), null, 'temporary_table_creation.log');
        }
    }
}