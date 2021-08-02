<?php

class Growthrocket_Faq_Block_Adminhtml_Grfaq_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("grfaqGrid");
				$this->setDefaultSort("id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("faq/grfaq")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{

            $this->addColumn("question", array(
            "header" => Mage::helper("faq")->__("Question"),
            "index" => "question",
            ));

            $this->addColumn("page_type", array(
            "header" => Mage::helper("faq")->__("Page Type"),
            "index" => "page_type",
            'type'      => 'options',
            "options" =>  Mage::helper('faq')->getPartNames()
            ));

            $this->addColumn("store_ids", array(
            "header" => Mage::helper("faq")->__("Store"),
            "index" => "store_ids",
            'type'          => 'store',
            'store_all'     => true,
            'store_view'    => true,
            'sortable'      => false,
            'filter_condition_callback' => array($this, '_filterStoreCondition'),
            'renderer'  => 'Growthrocket_Faq_Block_Adminhtml_Grfaq_Store',
            ));

            $this->addColumn("status", array(
            "header" => Mage::helper("faq")->__("Status"),
            "index" => "status",
            'type'      => 'options',
            'options'   => array(
                0 => Mage::helper('cms')->__('Disabled'),
                1 => Mage::helper('cms')->__('Enabled')
            )
            ));

            $this->addColumn("position", array(
                "header" => Mage::helper("faq")->__("Position"),
                "index" => "position",
            ));


            $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			   return $this->getUrl("*/*/edit", array("id" => $row->getId()));
		}

        protected function _filterStoreCondition($collection, $column)
        {
            if (!$value = $column->getFilter()->getValue()) {
                return;
            }

            $this->getCollection()->addFieldToFilter('store_ids', ['finset' => $value]);
        }
		
		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('id');
			$this->getMassactionBlock()->setFormFieldName('ids');
			$this->getMassactionBlock()->setUseSelectAll(true);
			$this->getMassactionBlock()->addItem('remove_grfaq', array(
					 'label'=> Mage::helper('faq')->__('Remove FAQ Item'),
					 'url'  => $this->getUrl('*/adminhtml_grfaq/massRemove'),
					 'confirm' => Mage::helper('faq')->__('Are you sure?')
				));
			return $this;
		}
			

}