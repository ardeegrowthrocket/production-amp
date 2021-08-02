<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/28/17
 * Time: 11:02 PM
 */

class Homebase_Auto_Block_Layered_View_Year extends Homebase_Auto_Block_Layered_View{

    protected $_yearCollection;

    public function __construct()
    {
        parent::__construct();
    }

    protected function _prepareLayout()
    {
        $this->getList();
        parent::_prepareLayout();
    }

    public function getList()
    {

        if(!$this->_yearCollection) {

            $params = unserialize($this->getRequest()->getParam('ymm_params'));
            /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
            $_reader = $this->_resource->getReadConnection();
            /** @var Varien_Db_Statement_Pdo_Mysql $result */
            $result = $_reader->select()
                ->from(array('var' => $this->getEntityVarchar()))
                ->join(array('combi' => $this->getCombinationTable()), 'var.entity_id=combi.product_id')
                ->join(array('s' => $this->getEntityStatus()), 'var.entity_id=s.entity_id', array('statusid' => 'attribute_id', 'statusval' => 'value'))
                ->joinLeft(array('label' => 'auto_combination_list_labels'), 'label.option=combi.year', array('label_year' => 'label.label'))
                ->where('s.attribute_id=?', 96)
                ->where('s.value=?', 1)
                ->where('var.attribute_id=?', $this->getAttributeId())
                ->where('var.value = ? ', $params['part'])
                ->where('combi.make = ? ', $params['make'])
                ->where('combi.model = ? ', $params['model'])
                ->order('label_year DESC')
                ->group('combi.year')
                ->query();
            $filterCollection = new Varien_Data_Collection();
            $yearArray = array();
            foreach ($result as $item) {
                $itemObject = new Varien_Object();
                $label = $this->_helper->getRawOptionText('year', $item['year']);
                $makeLabel = $this->_helper->getOptionText('make', $item['make']);
                $modelLabel = $this->_helper->getOptionText('model', $item['model']);
                $urlFriendly = $this->_helper->filterTextToUrl($label) . '-' . $makeLabel . '-' . $modelLabel . '-' . $this->_helper->filterTextToUrl($params['part']);
                $itemObject->setData(array(
                    'id' => $item['year'],
                    'label' => $label,
                    'link' => $this->_helper->generateLink($urlFriendly, 'part-ymm')
                ));
                $filterCollection->addItem($itemObject);
                $yearArray[] = $label;
            }

            if(empty(Mage::registry('meta_year_data'))){
                Mage::register('meta_year_data',$yearArray);
            }

            $this->_yearCollection = $filterCollection;
        }
        return $this->_yearCollection ;
    }

    public function getLayerTitle()
    {
        return 'Year';
    }
}