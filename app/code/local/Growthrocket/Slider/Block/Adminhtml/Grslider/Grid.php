<?php

class Growthrocket_Slider_Block_Adminhtml_Grslider_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("grsliderGrid");
				$this->setDefaultSort("id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("slider/grslider")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{
		    
				$this->addColumn("id", array(
				"header" => Mage::helper("slider")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "id",
				));
                
				$this->addColumn("title", array(
				"header" => Mage::helper("slider")->__("Title"),
				"index" => "title",
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

            $this->addColumn("is_active", array(
                "header" => Mage::helper("faq")->__("Status"),
                "index" => "is_active",
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
			$this->getMassactionBlock()->addItem('remove_grslider', array(
					 'label'=> Mage::helper('slider')->__('Remove Grslider'),
					 'url'  => $this->getUrl('*/adminhtml_grslider/massRemove'),
					 'confirm' => Mage::helper('slider')->__('Are you sure?')
				));
			return $this;
		}
			

}