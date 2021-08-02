<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('rulesGrid');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}
	
	protected function _prepareCollection()
	{
		$collection = Mage::getModel('aitcbp/rule')->getCollection()
			//->addGroups()
			;
		
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
		
		$this->addColumn('rule_name', array(
			'header'	=> Mage::helper('aitcbp')->__('Rule Name'),
			'index'		=> 'rule_name',
		));
		
		$this->addColumn('in_groups', array(
			'header'	=> Mage::helper('aitcbp')->__('Rule Groups'),
			'index'		=> 'in_groups',
			'renderer'	=> 'aitcbp/adminhtml_rules_column_renderer_group',
			'align'     => 'left',
			'filter'    => 'aitcbp/adminhtml_rules_column_filter_group',
			'options'   => Mage::getModel('aitcbp/group')->getCollection()->getAsOptions(),
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
		
		$this->addColumn('priority', array(
			'header'	=> Mage::helper('aitcbp')->__('Priority'),
			'index'		=> 'priority',
			'align'     => 'left',
            'width'     => '80px',
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