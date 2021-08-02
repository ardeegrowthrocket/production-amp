<?php

class Homebase_Finder2_Block_Adminhtml_Finder2_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("finder2Grid");
				$this->setDefaultSort("id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("finder2/finder2")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{
				$this->addColumn("id", array(
				"header" => Mage::helper("finder2")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "id",
				));
                
				$this->addColumn("year", array(
				"header" => Mage::helper("finder2")->__("Year"),
				"index" => "year",
				));
				$this->addColumn("make", array(
				"header" => Mage::helper("finder2")->__("Make"),
				"index" => "make",
				));
				$this->addColumn("model", array(
				"header" => Mage::helper("finder2")->__("Model"),
				"index" => "model",
				));
				$this->addColumn("category", array(
				"header" => Mage::helper("finder2")->__("Category Reference"),
				"index" => "category",
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
			$this->getMassactionBlock()->addItem('remove_finder2', array(
					 'label'=> Mage::helper('finder2')->__('Remove Finder2'),
					 'url'  => $this->getUrl('*/finder2_index/massRemove'),
					 'confirm' => Mage::helper('finder2')->__('Are you sure?')
				));
			return $this;
		}
			

}