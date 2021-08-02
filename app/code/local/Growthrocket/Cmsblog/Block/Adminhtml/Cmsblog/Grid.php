<?php

class Growthrocket_Cmsblog_Block_Adminhtml_Cmsblog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("cmsblogGrid");
				$this->setDefaultSort("id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("cmsblog/cmsblog")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}

		protected function _prepareColumns()
		{

            $this->addColumn("id", array(
            "header" => Mage::helper("cmsblog")->__("ID"),
            "align" =>"right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
            ));
                
            $this->addColumn("title", array(
                "header" => Mage::helper("cmsblog")->__("Title"),
                "index" => "title",
            ));

            $this->addColumn('is_active', array(
                'header'    => Mage::helper('cms')->__('Status'),
                'index'     => 'is_active',
                'type'      => 'options',
                'options'   => array(
                    '0' => Mage::helper('cmsblog')->__('Disabled'),
                    '1' => Mage::helper('cmsblog')->__('Enabled'),
                ),
            ));


            $this->addColumn('store_ids', array(
                'header'        => Mage::helper('cms')->__('Store View'),
                'index'         => 'store_ids',
                'type'          => 'store',
                'store_all'     => true,
                'store_view'    => true,
                'sortable'      => false,
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));

						
            $this->addColumn('updated_date', array(
                'header'    => Mage::helper('cmsblog')->__('updated_date'),
                'index'     => 'updated_date',
                'type'      => 'datetime',
            ));
            $this->addColumn('created_date', array(
                'header'    => Mage::helper('cmsblog')->__('created_date'),
                'index'     => 'created_date',
                'type'      => 'datetime',
            ));

			$this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV')); 
			$this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			   return $this->getUrl("*/*/edit", array("id" => $row->getId()));
		}


		
		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('id');
			$this->getMassactionBlock()->setFormFieldName('ids');
			$this->getMassactionBlock()->setUseSelectAll(true);
			$this->getMassactionBlock()->addItem('remove_cmsblog', array(
					 'label'=> Mage::helper('cmsblog')->__('Remove Cmsblog'),
					 'url'  => $this->getUrl('*/adminhtml_cmsblog/massRemove'),
					 'confirm' => Mage::helper('cmsblog')->__('Are you sure?')
				));
			return $this;
		}

        protected function _filterStoreCondition($collection, $column)
        {
            if (!$value = $column->getFilter()->getValue()) {
                return;
            }

            $this->getCollection()->addStoreFilter($value);
        }
		

}