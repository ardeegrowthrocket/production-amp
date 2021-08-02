<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/28/17
 * Time: 10:52 PM
 */

class Homebase_Auto_Block_Layered_View_Model extends Homebase_Auto_Block_Layered_View{

    protected $_modelCollection;

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

        if(!$this->_modelCollection) {

            $params = unserialize($this->getRequest()->getParam('ymm_params'));
            $storeCode = $this->getRequest()->getStoreCodeFromPath();

            if (!array_key_exists('make', $params)) {

                if ($storeCode == 'spp_en' || $storeCode == 'sop') {
                    $params['make'] = 422;
                } else if ($storeCode == 'jau_en') {
                    $params['make'] = 304;
                } else if ($storeCode == 'rau_en') {
                    $params['make'] = 305;
                }

            }
            /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
            $_reader = $this->_resource->getReadConnection();
            /** @var Varien_Db_Statement_Pdo_Mysql $result */
            $result = $_reader->select()
                ->from(array('var' => $this->getEntityVarchar()))
                ->join(array('combi' => $this->getCombinationTable()), 'var.entity_id=combi.product_id')
                ->join(array('s' => $this->getEntityStatus()), 'var.entity_id=s.entity_id', array('statusid' => 'attribute_id', 'statusval' => 'value'))
                ->joinLeft(array('label' => 'auto_combination_list_labels'), 'label.option=combi.model', array('label_model' => 'label.label'))
                ->where('s.attribute_id=?', 96)
                ->where('s.value=?', 1)
                ->where('var.attribute_id=?', $this->getAttributeId())
                ->where('var.value = ? ', $params['part'])
                ->where('combi.make = ? ', $params['make'])
                ->order('label_model ASC')
                ->group('combi.model')
                ->query();

            $filterCollection = new Varien_Data_Collection();
            $modelArray = array();
            foreach ($result as $item) {

                $itemObject = new Varien_Object();
                $name = Mage::helper('hautopart/parser')->getLabel($item['model'], 'name');
                $label = $this->_helper->getRawOptionText('model', $item['model']);
                $makeLabel = $this->_helper->getOptionText('make', $item['make']);
                $urlFriendly = $makeLabel . '-' . $this->_helper->filterTextToUrl($label) . '-' . $this->_helper->filterTextToUrl($params['part']);
                $itemObject->setData(array(
                    'id' => $item['model'],
                    'label' => $name,
                    'link' => $this->_helper->generateLink($urlFriendly, 'part-model')
                ));
                $filterCollection->addItem($itemObject);
                $modelArray[] = $name;
            }

            $this->_modelCollection = $filterCollection;

            if(empty(Mage::registry('meta_model_data'))){
                Mage::register('meta_model_data',$modelArray);
            }
        }

        return $this->_modelCollection;
    }

    public function getLayerTitle()
    {
        return 'Model';
    }
}
