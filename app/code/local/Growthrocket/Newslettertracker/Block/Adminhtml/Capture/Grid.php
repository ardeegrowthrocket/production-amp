<?php

class Growthrocket_Newslettertracker_Block_Adminhtml_Capture_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("captureGrid");
				$this->setDefaultSort("id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("newslettertracker/capture")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{
				$this->addColumn("id", array(
				"header" => Mage::helper("newslettertracker")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "id",
				));
                
				$this->addColumn("referrer", array(
				"header" => Mage::helper("newslettertracker")->__("referrer"),
				"index" => "referrer",
				));
				$this->addColumn("email", array(
				"header" => Mage::helper("newslettertracker")->__("Email"),
				"index" => "email",
				));
				$this->addColumn("current", array(
				"header" => Mage::helper("newslettertracker")->__("current"),
				"index" => "current",
				));
				$this->addColumn("ip", array(
				"header" => Mage::helper("newslettertracker")->__("ip"),
				"index" => "ip",
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
			$this->getMassactionBlock()->addItem('remove_capture', array(
					 'label'=> Mage::helper('newslettertracker')->__('Remove Capture'),
					 'url'  => $this->getUrl('*/adminhtml_capture/massRemove'),
					 'confirm' => Mage::helper('newslettertracker')->__('Are you sure?')
				));
			return $this;
		}
			

}