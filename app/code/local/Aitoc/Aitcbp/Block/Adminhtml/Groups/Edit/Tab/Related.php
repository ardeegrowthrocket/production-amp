<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups_Edit_Tab_Related extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
    {
        parent::__construct();
        $this->setId('aitcbp_group_product');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->setDefaultFilter(array('in_group'=>1));
    }
    
	protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_group') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
            }
            else {
                if($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$productIds));
                }
            }
        }
        else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
    
    protected function _prepareCollection()
    {
    	$collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('cbp_group')
            ;
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection->addFieldToFilter('type_id', array('nin'=>array('grouped')));
        $this->setCollection($collection);
        
        Mage::register('current_cbp_group', $collection);
        
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
    	$this->addColumn('in_group', array(
            'header_css_class' => 'a-center',
            'type'      => 'checkbox',
            'name'      => 'in_group',
            'values'    => $this->_getSelectedProducts(),
            'align'     => 'center',
            'index'     => 'entity_id'
        ));
    	$this->addColumn('entity_id', array(
            'header'    => Mage::helper('catalog')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'entity_id'
        ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Name'),
            'index'     => 'name'
        ));
        $this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'width'     => '80',
            'index'     => 'sku'
        ));
        $this->addColumn('price', array(
            'header'    => Mage::helper('catalog')->__('Price'),
            'type'  => 'currency',
            'width'     => '1',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
            'index'     => 'price'
        ));
        
    	return parent::_prepareColumns();
    }
    
	protected function _getSelectedProducts()
    {
    	$group = Mage::registry('aitacbp_groups_data');
//    	d($group->getAssociatedProducts());
    	return $group->getAssociatedProducts();
//        $products = $this->getRequest()->getPost('in_group_products');
//        d($products, 1);
//        d($products, 1);
//        if (is_null($products)) {
//            $products = $this->getCategory()->getProductsPosition();
//            return array_keys($products);
//        }
        return $products;
    }
    
}
?>