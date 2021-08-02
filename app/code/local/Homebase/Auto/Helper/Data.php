<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/22/17
 * Time: 3:54 PM
 */

class Homebase_Auto_Helper_Data extends Mage_Core_Helper_Data{
    /** @var Mage_Core_Model_Resource $_resource */
    private $_resource;

    public function __construct(){

        $this->_resource = Mage::getSingleton('core/resource');
    }

    /**
     * @param $path
     * @param $route
     * @param $controller Mage_Core_Controller_Front_Action
     */
    public function validAutoRoute($path, $route, $controller){
        if($controller instanceof Mage_Core_Controller_Front_Action){
            $storeCode = $controller->getRequest()->getStoreCodeFromPath();
            $_store = Mage::getModel('core/store')->load($storeCode,'code');
            $storeId = $_store->getStoreId();
            $fitmentUrlHelper = Mage::helper('hfitment/url');
            return $fitmentUrlHelper->validateRoute($path,$route, $storeId);
        }
        return false;
    }

    public function fetchRouteParams($path, $route, $controller){
        if($controller instanceof Mage_Core_Controller_Front_Action){
            $storeCode = $controller->getRequest()->getStoreCodeFromPath();
            $_store = Mage::getModel('core/store')->load($storeCode,'code');
            $storeId = $_store->getStoreId();
            $fitmentUrlHelper = Mage::helper('hfitment/url');
            return $fitmentUrlHelper->getCombinationSerialFromRoutePath($path,$route, $storeId);
        }
        return array();
    }

    public function fetchProductEntityId($fitmentValueArray){
        $_reader = $this->_resource->getConnection('core_read');
        $select = $_reader->select()
            ->from($this->_resource->getTableName('hautopart/combination_list'));

        if(!is_array($fitmentValueArray)){
            throw new Exception('Expects an array key-pair value');
        }
        foreach($fitmentValueArray as $fitment){
          foreach($fitment as $column => $value) {
              $select->where($column . '= ?', $value);
          }
        }
        $select->group('product_id');
        $result = $select->query();
        return $result->fetchAll(PDO::FETCH_COLUMN,1);
    }

    /**
     * @param $block Smartwave_Porto_Block_Html_Head
     */
    public function getProductListingCount($block){
        /** @var Homebase_Auto_Block_Product_Listing $listingBlock */
        $listingBlock = $block->getLayout()->getBlock('product-listing');
        return $listingBlock->getCollection()->count();
    }

    /**
     * @param $layout Smartwave_All_Model_Core_Layout
     */
    public function getModelList($layout){
        /** @var Homebase_Auto_Block_Layered_View_Model $_block */
        $_block = $layout->getBlock('layered-proxy');
        /** @var Varien_Data_Collection $_list */
        $_list = $_block->getList();
        $labels = $_list->getColumnValues('label');
        return implode(',', $labels);

    }

    /**
     * Redirect if single product listing
     * @param $productCollection
     */
    public function redirectSingleProduct($productCollection)
    {
        $store = Mage::app()->getStore();
        $specificStore = array('jau');

        $request = Mage::app()->getRequest();
        if($productCollection->getSize() == 1 && in_array(strtolower($store->getName()), $specificStore)) {
            $product = $productCollection->getFirstItem();
            if(!empty($productCollection->getFirstItem()) && $request->getActionName() != 'cat' ){
                Mage::app()->getFrontController()->getResponse()->setRedirect($product->getProductUrl(),301);

            }else{
                return;
            }
        }
        return;
    }

    /**
     *
     */
    public function getAutoLabelById($optionId)
    {
        $cacheId = 'auto_label_combination';
        if (false !== ($data = Mage::app()->getCache()->load($cacheId))) {
            $data = unserialize($data);
        } else {

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');

            $query = 'SELECT `option`,`label` FROM `auto_combination_list_labels`';
            $results = $readConnection->fetchAll($query);
            $data = array();
            foreach ($results as $row) {
                $data[$row['option']] = $row['label'];
            }
            Mage::app()->getCache()->save(serialize($data), $cacheId);
        }

        if (isset($data[$optionId])) {
            return $data[$optionId];
        } else {
            return false;
        }
    }

    public function getYmmFilter()
    {

        $request = Mage::app()->getRequest();
        $ymmFilter = array();
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $param = unserialize($request->getParam('ymm_params'));
        $eavConn = Mage::getResourceModel('core/config');
        $resource = Mage::getSingleton('core/resource');
        $catalogWebsite = $resource->getTableName('catalog/product_website');
        $varAttrTable = $eavConn->getValueTable('catalog/product','varchar');
        $_reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $_reader->select()->from(array('auto' => $resource->getTableName('hautopart/combination_list')));
        $select->join(array('website' => $catalogWebsite),'website.product_id=auto.product_id and website_id = ' .  $websiteId);
        $select->join(array('at' => $varAttrTable),'at.entity_id=auto.product_id and at.attribute_id=251',array('auto_type' => 'value'));
        $select->join(array('make_label' => 'auto_combination_list_labels'),'make_label.option=auto.make',array('make_label' => 'label'));
        $select->join(array('model_label' => 'auto_combination_list_labels'),'model_label.option=auto.model',array('model_label' => 'label'));
        $select->join(array('year_label' => 'auto_combination_list_labels'),'year_label.option=auto.year',array('year_label' => 'label'));


        if($request->getActionName() == 'cat'){
            $ymmParam = unserialize($request->getParam('ymm_params'));
            $select->where('FIND_IN_SET(' . $ymmParam['category'] . ',at.value)');
            if(isset($ymmParam['model'])){
                $select->where('make=' . $ymmParam['make']);
            }
            if(isset($ymmParam['model'])){
                $select->where('model=' . $ymmParam['model']);
            }
            if(isset($ymmParam['model'])){
                $select->where('year=' . $ymmParam['year']);
            }

        }else{
            $select->where('FIND_IN_SET(' . $param['category'] . ',at.value)');
            if(!empty($request->getParam('make'))){
                $select->where('make=' . $request->getParam('make'));
            }
            if(!empty($request->getParam('model'))){
                $select->where('model=' . $request->getParam('model'));
            }
            if(!empty($request->getParam('year'))){
                $select->where('year=' . $request->getParam('year'));
            }
        }


        $result = $select->query();

        foreach ($result as $row) {
            $ymmFilter['make'][$row['make']] = $row['make_label'];
            $ymmFilter['model'][$row['model']] = $row['model_label'];
            $ymmFilter['year'][$row['year']] = $row['year_label'];
            $ymmFilter['product_id'][$row['product_id']] = $row['product_id'];
        }

        asort($ymmFilter['model']);
        arsort($ymmFilter['year']);

        return $ymmFilter;

    }

}
