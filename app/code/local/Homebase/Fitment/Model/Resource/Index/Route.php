<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/17
 * Time: 9:59 PM
 */

class Homebase_Fitment_Model_Resource_Index_Route extends Mage_Index_Model_Resource_Abstract{

    /**
     * Store id
     *
     * @var int
     */
    protected $_storeId                  = null;

    protected $_allowTableChanges        = true;

    protected $_columnsSql               = null;

    protected $_fitmentQuery = array();

    protected $_marker                  = null;
    protected $optionsData = array();

    /** @var Homebase_Fitment_Helper_Url $urlHelper  */
    private $urlHelper = null;

    CONST PAGE_ROW_LIMIT = 50;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        // TODO: Implement _construct() method.
        $this->_init('hfitment/fitment_route','id');
        $this->urlHelper = Mage::helper('hfitment/url');
        $this->_marker = time();
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }
    public function getStoreId()
    {
        if (is_null($this->_storeId)) {
            return (int)Mage::app()->getStore()->getId();
        }
        return $this->_storeId;
    }
    public function getMainTable()
    {
        return $this->getMainStoreTable($this->getStoreId());
    }
    public function getMainStoreTable($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        if (is_string($storeId)) {
            $storeId = intval($storeId);
        }
        if ($this->getUseStoreTables() && $storeId) {
            $suffix = sprintf('store_%d', $storeId);
            $table = $this->getTable(array('hfitment/fitment_route', $suffix));
        } else {
            $table = parent::getMainTable();
        }

        return $table;
    }
    public function getUseStoreTables()
    {
        return true;
    }
    public function rebuild($stores = null){

        //OPTION PRELOADED

        $reader = $this->_getReader();
        $select = $reader->select()
            ->from(array('product' => 'eav_attribute_option_value'));

         $results = $this->_getReader()->query($select);

         foreach($results as $data){
            $this->optionsData[$data['option_id']] = $data['value'];
         }





        $this->_getReader()->query("INSERT INTO catalog_product_entity_varchar (entity_type_id,attribute_id,store_id,entity_id,value)  SELECT entity_type_id,attribute_id,store_id,entity_id,value FROM catalog_product_entity_text WHERE attribute_id=251 ON DUPLICATE KEY UPDATE value=VALUES(value)");




        if($stores === null){
            $stores = Mage::app()->getStores();
        }
        if (!is_array($stores)) {
            $stores = array($stores);
        }
        foreach($stores as $store){
            $allowedstores = array();
            if(Mage::getStoreConfig('hfitment/settings/enable')){
                $allowedstores = explode(',', Mage::getStoreConfig('hfitment/settings/stores'));
                if(in_array($store->getId(), $allowedstores)){
                    if($this->_allowTableChanges){
                        #$this->_createTable($store->getId());
                    }
                    /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
                    $this->buildRoute($store->getId());
                }
            }else{
                if($this->_allowTableChanges){
                    #$this->_createTable($store->getId());
                }
                /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
                $this->buildRoute($store->getId());
            }
        }
    }
    protected function _createTable($store){

        $tableName = $this->getMainStoreTable($store);


        $results = $this->_getReader()->query("SHOW COLUMNS FROM $tableName LIKE 'marker'");









        $counter = 0;
        foreach($results as $d){
            $counter = count($d);
        }

        if(!empty($counter)){
            return;
        }
        /** @var Magento_Db_Adapter_Pdo_Mysql $writer */
        $writer = $this->_getWriteAdapter();
        echo 'Dropping Table >> ' . $tableName . "\n";
        $writer->dropTable($tableName);

        /** @var Varien_Db_Ddl_Table $table */
        $table = $this->_getWriteAdapter()
            ->newTable($tableName)
            ->setComment('Enhanced Fitment Route Indexer');
        if($this->_columnsSql === null){
            $table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true
            ),'Combination Id')
                ->addColumn('route',Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ),'Combintation parent route')
                ->addColumn('path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ),'Combination path')
                ->addColumn('combination',Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ),'Serialized Auto combination')
                ->addColumn('marker',Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                    'nullable'  => false,
                ),'markerforindex')                
                ->addIndex($writer->getIndexName($tableName,array('route','path')),array('route','path'),array('type' => 'unique'));
        }
        $writer->createTable($table);
    }
    protected function _createTables(){
        if($this->_allowTableChanges){
            foreach (Mage::app()->getStores() as $store) {
                $allowedstores = array();
                if(Mage::getStoreConfig('hfitment/settings/enable')){
                    $allowedstores = explode(',', Mage::getStoreConfig('hfitment/settings/stores'));
                    if(in_array($store->getId(), $allowedstores)){
                        $this->_createTable($store->getId());
                    }
                }else{
                    $this->_createTable($store->getId());
                }
            }
        }
        return $this;
    }
    public function reindexAll(){
        $this->_createTables();
        $allowTableChanges = $this->_allowTableChanges;
        if ($allowTableChanges) {
            $this->_allowTableChanges = false;
        }
        $this->beginTransaction();
        try {
            $this->rebuild();
            $this->commit();
            if ($allowTableChanges) {
                $this->_allowTableChanges = true;
            }
        } catch (Exception $e) {
            $this->rollBack();
            if ($allowTableChanges) {
                $this->_allowTableChanges = true;
            }
            throw $e;
        }
        return $this;
    }
    /**
     * @param $storeId int
     */
    protected function buildRoute($storeId){
        echo "Building route for " . $storeId . "\n";
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $this->buildPartYmmRoutes($storeId,1);
        echo "Fitment routes for " . $storeId . "\n";
        $this->buildPartRoutes($storeId,1);
        echo "Part YMM routes for " . $storeId . "\n";
        $this->buildCategoryRoutes($storeId,1);
        echo "Cat routes for " . $storeId . "\n";
        $this->buildFitmentRoutes($storeId, $serialSelectTotal);
        echo "Cat Fitment Routes Building for " . $storeId . "\n";
        $this->buildPartNameRoutes($storeId,1);
        echo "Partname routes for " . $storeId . "\n";
        $storetable = $this->getMainStoreTable($storeId);
        $marker = $this->_marker;
        $this->_getReader()->query("DELETE FROM $storetable WHERE marker!='$marker'");
        echo "Remove not needed routes (marker:$marker) for " . $storeId . "\n";



        $this->urlHelper->_query("INSERT INTO auto_combination_index (route,path,combination,store_id) SELECT  route,path,combination,(0) FROM fitment_route_store_{$storeId} ON DUPLICATE KEY UPDATE route = VALUES(route),path = VALUES(path),combination = VALUES(combination)");

        echo "Reindex store auto_combination_index {$storeId} for " . $storeId . "\n";

        //SELECT * FROM fitment_route_store_1 WHERE route IN ('cat','category','make','model','year') GROUP by path,route
        $this->commit();




    }

    /**
     *
     * Responsible for generating the category routes without fitment
     * /category/xxx.html
     * @param $storeId
     * @param $rowTotal
     */
    public function buildCategoryOnlyRoutes($storeId,$rowTotal){
            $select = $this->getCategoryFitmentSelectQueryWithSerial($storeId,251);
            $results = $this->_getReader()->query($select);
            $this->_buildCategory($results, $storeId);
    }




    /**
     * Responsible for generating the part routes
     * /part/xxx.html
     *
     * @param $storeId
     * @param $rowTotal
     */
    public function buildPartNameRoutes($storeId,$rowTotal){


        $totalPages = ((int)ceil($rowTotal / self::PAGE_ROW_LIMIT));
            $select = $this->getFitmentSelectQuery($storeId,249);
            if(Mage::getStoreConfig('fitment/configuration/enable', $storeId)){
                $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $storeId));
                $select->where('product.make IN (?)', $allowedMakes);
            }
            $select->reset(Zend_Db_Select::GROUP);
            $select->group('value');
            $results = $this->_getReader()->query($select);
            $this->_buildPartNameRoutes($results, $storeId);
    }

    /**
     * Responsible for generating the part and ymm routes
     * /partymm/xxx.html
     * @param $storeId
     * @param $rowTotal
     */


    public function _buildUrls($segment,$a=0){

       $fitment = $this->urlHelper->extractPermittedKeys($segment);


        foreach($fitment as $key=>$value){

            if(is_numeric($value)){
                $fitment[$key] = $this->optionsData[$value];
            }
        }



       $path = array();
       foreach($fitment as $key => $value){
             $path[] = $this->urlHelper->filterTextToUrl($value);
       }
        if(!empty($path)){
            $url = implode('-', $path);
        }
        return $url;

    }
    public function buildPartYmmRoutes($storeId,$rowTotal){
        $totalPages = ((int)ceil($rowTotal / self::PAGE_ROW_LIMIT));
        $table = $this->getMainStoreTable($storeId);

            $select = $this->getFitmentSelectQueryWithSerial($storeId,249);

            $results = $this->_getReader()->query($select);
            $routes = array();
            foreach($results as $result){

                if($result['attribute_id'] == 249){
                    if(isset($result['value']) && $result['value'] !==''){
                        if(Mage::getStoreConfig('fitment/configuration/enable', $storeId)){
                            $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $storeId));
                            if(!in_array($result['make'],$allowedMakes)){
                                continue;
                            }
                        }
                        $result['part'] = $result['value'];
                        array_push($routes,$result);
                    }
                }
            }
            // Year-Make-Model-Part Routes
            $partYmmRouteRecords = array();
            foreach($routes as $idx => $route){

                $segment = $this->_segment($route,array('year','part','make','model'));





                $path = $this->_buildUrls($segment,$storeId);
                if($path != null && $path != ''){
                    $record = array(
                        'route' => 'partymm',
                        'path'  => $path,
                        'combination' => serialize($segment)
                    );



                    array_push($partYmmRouteRecords,$record);
                }

                if(count($partYmmRouteRecords) >= 100){
                    echo $this->_insertonDup($table,$partYmmRouteRecords);
                    $partYmmRouteRecords = array();
                }
                

            }
            if(count($partYmmRouteRecords) > 0){
                echo $this->_insertonDup($table,$partYmmRouteRecords);
            }
    }

    /**
     *  Responsible for generating the cat and category routes
     * /cat/xxx.html
     * /category/xxx.html
     * @param $storeId
     * @param $rowTotal
     */
    public function buildCategoryRoutes($storeId, $rowTotal)
    {
        $totalPages = ((int)ceil($rowTotal / self::PAGE_ROW_LIMIT));
            $select = $this->getFitmentSelectQueryWithSerial($storeId,251);
            $results = $this->_getReader()->query($select);
            $this->_buildCategory($results, $storeId);
    }
    /**
     * Responsible for generating the part make and part model routes.
     * /partmake/{make}-{part}.html
     * /partmodel/{make}-{model}-{part}.html
     *
     * @param $storeId
     * @param $rowTotal
     */
    public function buildPartRoutes($storeId,$rowTotal){
        $totalPages = ((int)ceil($rowTotal / self::PAGE_ROW_LIMIT));
            $select = $this->getFitmentSelectQueryWithSerial($storeId,249);
            $results = $this->_getReader()->query($select);
            $this->_buildPartRoutes($results, $storeId);
    }

    /**
     * Responsible for generating the make, model and year routes.
     * /year/xxx.html
     * /make/xxx.html
     * /model/xxx.html
     * @param $storeId
     * @param $rowTotal
     */
    public function buildFitmentRoutes($storeId,$rowTotal= 1){
            $select = $this->getFitmentSelectQueryWithSerial($storeId,249);
            $results = $this->_getReader()->query($select);
            $this->_buildFitment($results, $storeId);
    }

    /**
     * @param $storeId
     * @return Varien_Db_Select
     */
    public function getFitmentSelectQuery($storeId,$attrib = 0){


        $reader = $this->_getReader();
        $productTable = $this->getTable('catalog/product');
        $productWebsites = $this->getTable('catalog/product_website');
        $fitmentTable = $this->getTable('hautopart/combination_list');
        $valueTable = $this->getValueTable('catalog/product','varchar');
        $statusTable = $this->getValueTable('catalog/product','int');
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load($storeId);
        $select = $reader->select()
            ->from(array('product' => $fitmentTable))
            ->where('product_id IN (SELECT product_id FROM catalog_product_website WHERE website_id='.$store->getWebsiteId().')');


        if(!empty($attrib)){


            $select->columns(array('value' => new Zend_Db_Expr("(SELECT value FROM catalog_product_entity_varchar WHERE attribute_id=".$attrib." AND entity_id=product.product_id  AND store_id IN (0,$storeId) ORDER by store_id DESC LIMIT 1)")));
            $select->columns(array('attribute_id' => new Zend_Db_Expr("(".$attrib.")")));
            $select->group('value');


        }


        $select->group('year');
        $select->group('make');
        $select->group('model');
        

        return $select;
    }


    public function getFitmentSelectQueryPartMake($storeId,$attrib=0){

        $reader = $this->_getReader();
        $productTable = $this->getTable('catalog/product');
        $productWebsites = $this->getTable('catalog/product_website');
        $fitmentTable = $this->getTable('hautopart/combination_list');
        $valueTable = $this->getValueTable('catalog/product','varchar');
        $statusTable = $this->getValueTable('catalog/product','int');
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load($storeId);
        $select = $reader->select()
            ->from(array('product' => $fitmentTable))
            ->where('product_id IN (SELECT product_id FROM catalog_product_website WHERE website_id='.$store->getWebsiteId().')');


        if(!empty($attrib)){


            $select->columns(array('value' => new Zend_Db_Expr("(SELECT value FROM catalog_product_entity_varchar WHERE attribute_id=".$attrib." AND entity_id=product.product_id)")));
            $select->columns(array('attribute_id' => new Zend_Db_Expr("(".$attrib.")")));
            $select->group('value');


        }

        $select->group('make');
        
        return $select;
    }



    public function getFitmentSelectQueryWithSerial($storeId,$attrib = 0){
        $select = $this->getFitmentSelectQuery($storeId,$attrib);
        return $select;
    }
    public function getCategoryFitmentSelectQueryWithSerial($storeId){
        $reader = $this->_getReader();
        $productTable = $this->getTable('catalog/product');
        $productWebsites = $this->getTable('catalog/product_website');
        $fitmentTable = $this->getTable('hautopart/combination_list');
        $valueTable = $this->getValueTable('catalog/product','varchar');
        $statusTable = $this->getValueTable('catalog/product','int');

        /** @var Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load($storeId);
        $select = $reader->select()
            ->from(array('product' => $productTable))
            ->joinLeft(array('fitment' => $fitmentTable),'product.entity_id = fitment.product_id')
            ->join(array('website' => $productWebsites),'product.entity_id = website.product_id')
            ->join(array('value' => $valueTable), 'value.entity_id = product.entity_id')
            ->join(array('stats' => $statusTable),'stats.entity_id=product.entity_id',array('statusid' => 'attribute_id','statusvalue'=> 'value'))
            ->where('website.website_id=?', $store->getWebsiteId())
            ->where('stats.attribute_id=?',96)
            ->where('stats.value=?',1)
            ->where('value.attribute_id IN (?) ', array(251))
            ->where('product.type_id = ?','autopart')
            ->where('value.value IS NOT NULL');
        $select->columns(array('partname' => new Zend_Db_Expr("CONCAT(value.value)")));
        $select->group('partname');
        return $select;
    }
    public function getLeftFitmentSelectQueryWithSerial($storeId){
        $reader = $this->_getReader();
        $productTable = $this->getTable('catalog/product');
        $productWebsites = $this->getTable('catalog/product_website');
        $fitmentTable = $this->getTable('hautopart/combination_list');
        $valueTable = $this->getValueTable('catalog/product','varchar');
        $statusTable = $this->getValueTable('catalog/product','int');
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load($storeId);
        $select = $reader->select()
            ->from(array('product' => $productTable))
            ->joinLeft(array('fitment' => $fitmentTable),'product.entity_id = fitment.product_id')
            ->join(array('website' => $productWebsites),'product.entity_id = website.product_id')
            ->join(array('value' => $valueTable), 'value.entity_id = product.entity_id')
            ->join(array('stats' => $statusTable),'stats.entity_id=product.entity_id',array('statusid' => 'attribute_id','status' => 'value'))
            ->where('website.website_id=?', $store->getWebsiteId())
            ->where('value.attribute_id IN (?) ', array(249))
            ->where('product.type_id = ?','autopart')
            ->where('value.value IS NOT NULL')
            ->where('stats.attribute_id=?',96)
            ->where('stats.value=?',1);
        $select->columns(array('partname' => new Zend_Db_Expr("CONCAT(value.value)")));
        $select->group('partname');
        return $select;
    }



    private function _buildPartNameRoutes($results, $storeId){
        $table = $this->getMainStoreTable($storeId);
        $routes = array();
        foreach($results as $result){
            if($result['attribute_id'] == 249){
                if(isset($result['value']) && $result['value'] !==''){
                    if(Mage::getStoreConfig('fitment/configuration/enable', $storeId)){
                        $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $storeId));
                        if(!in_array($result['make'],$allowedMakes)){
                            continue;
                        }
                    }
                    $result['part'] = $result['value'];
                    array_push($routes,$result);
                }
            }
        }
        // Part Routes
        $partRouteRecords = array();
        if($storeId == 4){
            array_push($routes,array('part' => 'Subaru Gear'));
        }
        foreach($routes as $idx => $route){
            $segment = $this->_segment($route,array('part'));
            $path = $this->_buildUrls($segment,$storeId);

            if(!empty($path) && $path !== ''){
                $record = array(
                    'route' => 'part',
                    'path'  => $path,
                    'combination' => serialize($this->_segment($route,array('part')))
                );

                #var_dump($record);
                array_push($partRouteRecords,$record);
            }
        }

        if(count($partRouteRecords) > 0){
            $this->_insertonDup($table,$partRouteRecords);
        }
    }


    private function _insertonDup($table,$parts){ 


        foreach($parts as $kk=>$dd){
            $parts[$kk]['marker'] = $this->_marker;
        }



        $row  = reset($parts);
        $cols = array_keys($row);

        $colsend = array();

        $data = array();

        foreach($cols as $d){
            $colsend[] = "$d  = VALUES($d)";
        }

        foreach($parts as $b){
            $maker = array();
            foreach ($b as $key => $value) {
                $value = addslashes($value);
                $maker[] = "'$value'";
            }
            $data[] = "(".implode(",",$maker).")";

        }

        
        $firstquery = "INSERT INTO $table (".implode(",",$cols).") VALUES ".implode(",",$data)." ON DUPLICATE KEY UPDATE ".implode(",",$colsend);
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        try{
            $this->commit();
             $this->urlHelper->_query($firstquery);
        }catch(Exception $e){
            echo $e->getMessage();
        }
        
       
    }
    private function _buildPartRoutes($results,$storeId){
        $table = $this->getMainStoreTable($storeId);
        $routes = array();
        foreach($results as $result){
            if($result['attribute_id'] == 249){
                if(isset($result['value']) && $result['value'] !==''){
                    if(Mage::getStoreConfig('fitment/configuration/enable', $storeId)){
                        $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $storeId));
                        if(!in_array($result['make'],$allowedMakes)){
                            continue;
                        }
                    }
                    $result['part'] = $result['value'];
                    array_push($routes,$result);
                }
            }
        }
        // Part-Make Routes
        $partMakeRouteRecords = array();

        $batchRoute = array();
        foreach($routes as $idx => $route){
            $segment = $this->_segment($route,array('part','make'));
            $path = $this->_buildUrls($segment,$storeId);
            if(!empty($path) && $path !== '') {

                $record = array(
                    'route' => 'partmake',
                    'path' => $path,
                    'combination' => serialize($segment)
                );


                array_push($batchRoute,$record);

                if(count($batchRoute) >= 100){
                    echo $this->_insertonDup($table,$batchRoute);
                    $batchRoute = array();
                }

            }
        }


        if(!empty(count($batchRoute)))
        {
            echo $this->_insertonDup($table,$batchRoute);
            $batchRoute = array();
        }


        // Make-Model-Part Routes
        $batchRoute = array();
        $partMakeModelRouteRecords = array();
        foreach($routes as $idx => $route){
            $segment = $this->_segment($route,array('part','make','model'));
            $path = $this->_buildUrls($segment,$storeId);
            if(!empty($path) && $path !== ''){




                $record = array(
                    'route' => 'partmodel',
                    'path'  => $path,
                    'combination' => serialize($segment)
                );

                array_push($batchRoute,$record);


                if(count($batchRoute) >= 100){
                    echo $this->_insertonDup($table,$batchRoute);
                    $batchRoute = array();
                }

            }
        }


        if(!empty(count($batchRoute)))
        {
            echo $this->_insertonDup($table,$batchRoute);
            $batchRoute = array();
        }


    }
    private function _buildFitment($results,$storeId){

        $table = $this->getMainStoreTable($storeId);
        $makeRoutes = array();
        $makeModelRoutes = array();
        $yearMakeModelRoutes = array();
        foreach($results as $item){
            if(Mage::getStoreConfig('fitment/configuration/enable', $storeId)){
                $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $storeId));
                if(!in_array($item['make'],$allowedMakes)){
                    continue;
                }
            }
            $this->_distinctArray($makeRoutes,$item, array('make'));
            $this->_distinctArray($makeModelRoutes,$item, array('make','model'));
            $this->_distinctArray($yearMakeModelRoutes,$item, array('make','model','year'));


        }
        $makeRouteRecords = array();
        foreach($makeRoutes as $route){
            $makeSegment = $this->_segment($route,array('make'));

            #var_dump($makeSegment);

            $path = $this->_buildUrls($makeSegment,$storeId);

             
            if($path != null && $path !=''){

                $record = array(
                    'route' => 'make',
                    'path'  => $path,
                    'combination' => serialize($this->serialCombination($makeSegment))
                );

                #var_dump($record);

                
                array_push($makeRouteRecords,$record);

            }
        }
        if(count($makeRouteRecords) > 0){
                $this->_insertonDup($table,$makeRouteRecords);
        }

        $makeModelRouteRecords = array();
        foreach($makeModelRoutes as $route){
            $segment = $this->_segment($route,array('make','model'));
            $path = $this->_buildUrls($segment,$storeId);
            if($path != null && $path !=''){

                $record = array(
                    'route' => 'model',
                    'path'  => $path,
                    'combination' => serialize($this->serialCombination($segment))
                );
                array_push($makeModelRouteRecords,$record);

            }
        }

        if(count($makeModelRouteRecords) > 0){
                $this->_insertonDup($table,$makeModelRouteRecords);
        }



        $yearMakeModelRouteRecords = array();
        foreach($yearMakeModelRoutes as $route){
            $segment = $this->_segment($route,array('make','model','year'));
            $path = $this->_buildUrls($segment,$storeId);
            if($path != null && $path !=''){

                $record = array(
                    'route' => 'year',
                    'path'  => $path,
                    'combination' => serialize($this->serialCombination($segment))
                );
                array_push($yearMakeModelRouteRecords,$record);

            }
        }

        if(count($yearMakeModelRouteRecords) > 0){
                $this->_insertonDup($table,$yearMakeModelRouteRecords);
        }

    }
    private function _buildCategory($results,$storeId){
        $uniqueArrays = array();
        foreach($results as $result){
            //Custom Category
            if($result['attribute_id'] == 251){
                if($result['value'] != ''){
                    if(Mage::getStoreConfig('fitment/configuration/enable', $storeId)){
                        $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $storeId));
                        if(!in_array($result['make'],$allowedMakes)){
                            continue;
                        }
                    }
                    $categories = explode(',',$result['value']);

                    foreach($categories as $c){
                        $bccat = $result;
                        $bccat['category'] = $c;
                        unset($bccat['value']);
                        $uniqueArrays[] = $bccat;
                    }
                }
            }
        }
        $table = $this->getMainStoreTable($storeId);
        $batchRoute = array();
        foreach($uniqueArrays as $route){
            // Make sure array keys' length is 6

            $catroute = array('category'=>$route['category']);

            $path = $this->_buildUrls($catroute,$storeId);
            if(!empty($path) && $path !== '') {

                $record = array(
                    'route' => 'category',
                    'path' => $path,
                    'combination'   => serialize(array('category' => $route['category']))
                );


                array_push($batchRoute,$record);

            }
            if(count($batchRoute) >= 100){
                echo $this->_insertonDup($table,$batchRoute);
                $batchRoute = array();
            }




        }

        if(count($batchRoute)!=0){
            echo $this->_insertonDup($table,$batchRoute);
            $batchRoute = array();
        }



        $batchRoute = array();
        foreach($uniqueArrays as $route){
            // Make sure array keys' length is 6

            #$catroute = array('category'=>$route['category']);

            $path = $this->_buildUrls($route,$storeId);
            if(!empty($path) && $path !== '') {

                $record = array(
                    'route' => 'cat',
                    'path' => $path,
                    'combination'   =>  serialize($this->serialCombination($route))
                );


                array_push($batchRoute,$record);

            }
            if(count($batchRoute) >= 100){
                echo $this->_insertonDup($table,$batchRoute);
                $batchRoute = array();
            }




        }

        if(count($batchRoute)!=0){
            echo $this->_insertonDup($table,$batchRoute);
            $batchRoute = array();
        }







    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    private function _getReader(){
        return $this->getReadConnection();
    }
    private function _definiteArray(&$array, $element, $route){
        $element['route']  = $route;
        $formattedArray = $this->_segment($element,array('entity_id','year','make','model','value','route'));
        if(empty($array)){
            array_push($array,$formattedArray);
            return;
        }
        $unique = true;
        foreach($array as $el){
            if($el['year'] == $formattedArray['year']
                && $el['make'] == $formattedArray['make'] && $el['model'] == $formattedArray['model']
                && $el['value'] == $formattedArray['value']){
                $unique = false;
            }
        }
        if($unique){
            array_push($array,$formattedArray);
        }
    }
    public function serialCombination($array){
        $segment = array();
        $keys = array_keys($array);

        foreach($keys as $key){
            if(in_array($key,array('year','make','model','category'))){
                $segment[$key] = $array[$key];
            }
        }
        return $segment;
    }
    private function _serialcombination($key){
        return in_array($key,array('year','make','model','category'));
    }
    private function _distinctArray(&$array,$nextElement,$keyCheck = array()){
        if(empty($array)){
            array_push($array,$nextElement);
        }else{
            $unique = true;
            foreach($array as $el){
                $currentElementSegment = array_intersect_key($el,array_flip($keyCheck));
                $newElementSegment = array_intersect_key($nextElement,array_flip($keyCheck));
                if($currentElementSegment == $newElementSegment){
                    $unique = false;
                }
            }
            if($unique){
                array_push($array,$nextElement);
            }
        }
    }
    private function _distinctPath(&$array,$nextElement){
        if(empty($array)){
            array_push($array,$nextElement);
        }else{
            $unique = true;
            foreach($array as $el){
                if(strcmp($el['path'],$nextElement['path']) == 0){
                    $unique = false;
                    break;
                }
            }
            if($unique){
                array_push($array,$nextElement);
            }
        }

    }
    private function _segment($array, $key){
        return array_intersect_key($array,array_flip($key));
    }

    /** Deprecated **/
    private function _convert($element){
        return in_array($element,array('entity_id','year','make','model','value','route'));
    }
    private function _getProductCollection($store){
        $_productCollection = Mage::getModel('catalog/product')->getCollection();
        $_productCollection->addStoreFilter($store);
        return $_productCollection;
    }
    public function isEnabled($productId){
        $reader = $this->_getReader();
        $statusTable = $this->getValueTable('catalog/product','int');
        $select = $reader->select()
            ->from(array('status' => $statusTable))
            ->where('attribute_id = ?', 96)
            ->where('value = ?',1)
            ->where('entity_id = ? ',$productId);
        $total = $this->_getReader()->query($select)->rowCount();
        return $total > 0;
    }
}
