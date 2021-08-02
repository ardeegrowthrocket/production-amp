<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('groupsGrid');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
	
	protected function _prepareCollection()
	{
		$collection = Mage::getModel('aitcbp/group')->getCollection();
		
		/* @var $collection Aitoc_Aitcbp_Model_Mysql4_Group_Collection */
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}
	
	protected function _prepareColumns()
	{
		$this->addColumn('entity_id', array(
			'header'	=> Mage::helper('aitcbp')->__('ID'),
			'index'		=> 'entity_id',
			'align'     => 'right',
            'width'     => '50px',
		));
		
		$this->addColumn('group_name', array(
			'header'	=> Mage::helper('aitcbp')->__('Group Title'),
			'index'		=> 'group_name',
		));
		
		$this->addColumn('cbp_type', array(
			'header'	=> Mage::helper('aitcbp')->__('Type'),
			'index'		=> 'cbp_type',
			'align'     => 'left',
            'width'     => '80px',
			'type'      => 'options',
			'options'   => array(
	              1 => Mage::helper('aitcbp')->__('Fixed'),
	              2 => Mage::helper('aitcbp')->__('Percent'),
	          ),
		));
		
		$store = $this->_getStore();
		$this->addColumn('amount', array(
			'header'		=> Mage::helper('aitcbp')->__('Amount'),
			'index'			=> 'amount',
			'renderer'		=> 'aitcbp/adminhtml_groups_column_renderer_amount',
            'currency_code'	=> $store->getBaseCurrency()->getCode(),
		));
		
		$this->addColumn('is_active', array(
			'header'	=> Mage::helper('aitcbp')->__('Status'),
			'index'		=> 'is_active',
			'align'     => 'left',
            'width'     => '80px',
			'type'      => 'options',
			'options'   => array(
	              1 => Mage::helper('aitcbp')->__('Active'),
	              0 => Mage::helper('aitcbp')->__('Inactive'),
	          ),
		));
		
		$this->addColumn('action',
            array(
                'header'    =>  Mage::helper('aitcbp')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('aitcbp')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		return parent::_prepareColumns();
	}
	
	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}
}
?>