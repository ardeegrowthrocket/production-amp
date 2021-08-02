<?php

class Homebase_Autopart_Block_Category_Partname extends Mage_Core_Block_Template
{

    protected $_partCollection = array();

    protected $_helper;

    protected $_params;

    protected $_actionName;

    protected $_makeModelLabel;

    public function _construct(){

        $this->_helper = Mage::helper('hautopart/parser');
        $this->_params = unserialize($this->getRequest()->getParam('ymm_params'));
        $this->_actionName = Mage::app()->getRequest()->getActionName();

        parent::_construct();
    }

    protected function _prepareLayout(){

        $this->_makeModelLabel = $this->getAutoName();
        $this->partCollection();

        return parent::_prepareLayout();
    }


    public function partCollection()
    {

        $model = $this->_params['model'];
        $year = $this->_params['year'];
        $pathHelper = Mage::helper('hauto/path');
        $baseUrl = Mage::getBaseUrl();

        $conditionsColumn = array(
            'model' => array("auto_model" => "auto.model","auto_make" => "auto.make"),
            'ymm' => array("auto_model" => "auto.model", "auto_year" => "auto.year", "auto_make" => "auto.make"),
        );

        if(empty($this->_partCollection)) {
            $resource = Mage::getSingleton('core/resource');
            $_store = Mage::app()->getStore();
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('part_name')
                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addAttributeToFilter('type_id',array('eq' => Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID))
                ->addWebsiteFilter($_store->getWebsite());

            $collection->getSelect()->joinLeft(
                array("auto" => $resource->getTableName('hautopart/combination_list')),
                "e.entity_id = auto.product_id",$conditionsColumn[$this->_actionName]);
            $collection->getSelect()->joinLeft(
                array("auto_model" => 'auto_combination_list_labels'),
                "auto_model.option = auto.model",
                array("auto_model_label" => "auto_model.label"));
            $collection->getSelect()->joinLeft(
                array("auto_make" => 'auto_combination_list_labels'),
                "auto_make.option = auto.make",
                array("auto_make_label" => "auto_make.label"));
            $collection->getSelect()->joinLeft(
                array("auto_model_name" => 'auto_combination_list_labels'),
                "auto_model_name.option = auto.model",
                array("auto_model_name" => "auto_model_name.name"));

            if($this->_actionName == 'ymm') {
                $collection->getSelect()->joinLeft(
                    array("auto_year" => 'auto_combination_list_labels'),
                    "auto_year.option = auto.year",
                    array("auto_year_label" => "auto_year.label"));

                $collection->getSelect()->where("auto.model={$model} && auto.year={$year}");
            }

            $collection->getSelect()->where("auto.model={$model}")->distinct('e.entity_id');

            foreach ($collection as $product) {

                $partName = $product->getPartName();
                if(!empty($partName)){

                    if($this->_actionName == 'ymm'){
                        $labelArray['year'] =  $product->getData('auto_year_label');
                    }

                    $labelArray['make'] = $product->getData('auto_make_label');
                    $labelArray['model'] = $product->getData('auto_model_label');
                    $labelArray['model_name'] = $product->getData('auto_model_name');
                    $labelArray['part'] = $product->getPartName();

                    $combLinkRaw = $labelArray;
                    $combNameRow = $labelArray;
                    unset($combNameRow['model']);
                    unset($combLinkRaw['model_name']);

                    $combName = implode(" ",  $combNameRow);
                    $combLink = implode(" ", $combLinkRaw);
                    $ymmLink = $pathHelper->filterTextToUrl($combLink);
                    $partLink = $this->_route($ymmLink);

                    $this->_partCollection[$partLink] = array(
                        'name' => $combName,
                        'link' => "{$baseUrl}{$partLink}.html"
                    );
                }
            }
            asort($this->_partCollection);
        }

        return $this->_partCollection;
    }

    public function getMakeModelName()
    {
        return $this->_makeModelLabel;
    }


    /**
     * get Route path
     * @return string
     */
    protected function _route($link)
    {
        $route = "";
        switch ($this->_actionName){
            case 'ymm' :
                $route = "part-ymm/";
                break;
            case 'model';
                $route = "part-model/";
                break;
        }

        return $route . $link;
    }

    public function getAutoName()
    {
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $ymm = array();
        foreach($params as $key => $param){
            if($key == 'model') {
                $name = $this->_helper->getLabel($param,'name');
            }else {
                $name = $this->_helper->getLabel($param);
            }
            $ymm[] = $name;
        }
        return implode(' ', $ymm);
    }

}