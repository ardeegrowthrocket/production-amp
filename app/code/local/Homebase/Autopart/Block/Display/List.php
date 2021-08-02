<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 2/24/17
 * Time: 12:12 AM
 */

class Homebase_Autopart_Block_Display_List extends Mage_Catalog_Block_Product_Abstract{

    protected $_helper;

    protected $_defaultToolbarBlock = 'hautopart/display_list_toolbar';

    protected $_productCollection;

    public function _construct()
    {
        parent::_construct(); // TODO: Change the autogenerated stub
        $this->_helper = Mage::helper('hautopart');
    }

    protected function _prepareLayout()
    {
        /** @var Mage_Page_Block_Html_Breadcrumbs $breadcrumbs */
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');

        if($breadcrumbs){
            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));
            $params = $this->_helper->getMakeModelQuery();
            foreach($params as $ndx => $param){
                $breadcrumbs->addCrumb('param' . $ndx, array(
                    'label'  => strtoupper($param),
                    'title' => ucfirst($param),
                    'link'  => (($ndx == 0 && count($params) > 1) ? $this->_helper->getModelPath($param) : '')
                ));
            }
        }
        return parent::_prepareLayout(); // TODO: Change the autogenerated stub
    }

    protected function _getProductList(){
        $params = $this->_helper->getMakeModelQuery();
        $options = array();
        $columns = array('make','model');
        foreach($params as $ndx => $param){
            /** @var Homebase_Autopart_Model_Resource_Label_Collection $_labels */
            $_labels = Mage::getModel('hautopart/label')->getCollection();
            $_labels->addExpressionFieldToSelect('llabel','LOWER(label)','label')
                ->getSelect()->having('llabel = ?',$param);
            $options[] = array($columns[$ndx] => $_labels->fetchItem()->getOption());
        }
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
        $_mixes = Mage::getModel('hautopart/mix')->getCollection();
        foreach($options as $indx => $value){
            foreach($value as $key => $val){
                $_mixes->addFieldToFilter($key,$val);

            }
        }
        $_mixes->getSelect()->group('product_id');
        $pIds = $_mixes->getColumnValues('product_id');

        if(!empty($pIds)){
            if(is_null($this->_productCollection)){
                $layer = $this->getLayer();
                $product_collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID))
                    ->addFieldToFilter('entity_id',array('in' => $pIds));
                $this->_productCollection = $product_collection;
                $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
            }
        }
        return $this->_productCollection;
    }
    public function getLoadedProductCollection()
    {
        return $this->_getProductList();
    }
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        $collection = $this->getLoadedProductCollection();

        // use sortable parameters
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }

        $toolbar->setCollection($collection);


        $this->setChild('toolbar',$toolbar);

        return parent::_beforeToHtml(); // TODO: Change the autogenerated stub
    }

    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    private function getOptionValueId($str){

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        /** @var String $table */
        $table = $resource->getTableName('eav/attribute_option_value');

        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getConnection('core_read');

        /** @var Varien_Db_Statement_Pdo_Mysql $statement */
        $query = 'SELECT * FROM ' . $table . ' WHERE LOWER(value) = :value';

        $statement = $reader->query($query ,array(
            'value' => strtolower($str)
        ));

        $results = $statement->fetchAll();

        foreach($results as $result){
            /** @var Mage_Eav_Model_Entity_Attribute_Option $_option */
            $_option = Mage::getModel('eav/entity_attribute_option')->load($result['option_id']);

            /** @var Mage_Eav_Model_Entity_Attribute $_attribute */
            $_attribute = Mage::getModel('eav/entity_attribute')->load($_option->getAttributeId());

            $productTypeId = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();

            if($_attribute->getEntityTypeId() == $productTypeId){
                return $_option;
            }
        }
        return null;
    }
    public function getToolbarBlock()
    {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }

    public function getMode()
    {
        return $this->getChild('toolbar')->getCurrentMode();
    }

    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }

    public function prepareSortableFieldsByCategory($category) {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($category->getAvailableSortByOptions());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = $category->getDefaultSortBy()) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    }
}