<?php
class Growthrocket_Compatibilitychecker_IndexController extends Mage_Core_Controller_Front_Action{

    protected  $_helper;

    protected $_enableFitmentMaker;

    protected $_fitmentMaker;

    protected function _construct()
    {
        $this->_helper = Mage::helper('compatibilitychecker');
        $this->_enableFitmentMaker = $configValue = Mage::getStoreConfig('fitment/configuration/enable');
        $this->_fitmentMaker = $configValue = Mage::getStoreConfig('fitment/configuration/make');
        parent::_construct();
    }

    public function IndexAction()
    {

        if (!$this->_validateFormKey()) {
            echo "Invalid Input";
            return;
        }

        $params = $this->getRequest()->getParams();
        $method = $params['method'];

        $result = array();
        switch ($method){
            case 'make':

                $collection = Mage::getModel('hautopart/combination')->getCollection();
                $collection->getSelect()->join(array('make' => 'auto_combination_list_labels'),' make=make.option',
                    array('llabel' => 'label'));
                $collection->addFieldToFilter('year', $params['year']);
                $collection->addFieldToFilter('main_table.store_id', $this->_websiteId());

                if($this->_enableFitmentMaker && !empty($this->_fitmentMaker)){
                    $collection->addFieldToFilter('make',array('in' => explode(',',$this->_fitmentMaker)));
                }

                $collection->getSelect()->group('label');
                $response = array();
                foreach($collection as $item) {
                    $response[] = array(
                        'id' => $item->getData('make'),
                        'label' => $item->getLlabel()
                    );
                }

                $result = $response;

            break;

            case  'model':

                $collection = Mage::getModel('hautopart/combination')->getCollection();
                $collection->getSelect()->join(array('model' => 'auto_combination_list_labels'),' model=model.option',
                    array('llabel' => 'label', 'name' => 'name'));
                $collection->addFieldToFilter('year', $params['year']);
                $collection->addFieldToFilter('make', $params['make']);
                $collection->getSelect()->group('label');
                $response = array();
                foreach($collection as $item) {
                    $response[] = array(
                        'id' => $item->getData('model'),
                        'label' => $item->getName()
                    );
                }

                $result = $response;

            break;

            case 'result':
                $result['result'] = $this->_result();

            break;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

    }

    /**
     * Check vehicle fitment
     */
    public function resultAction()
    {

        $result = array();
        $result['result'] = $this->_result();

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * @return array|void
     */
    protected function _result()
    {

        if (!$this->_validateFormKey()) {
            echo "Invalid Input";
            return;
        }

        $params = $this->getRequest()->getParams();

        $yearId = $params['year'];
        $makeId = $params['make'];
        $modelId = $params['model'];

        $ymmLabelArray = array(
            'year' => $this->_helper->getLabelById($yearId),
            'make' => $this->_helper->getLabelById($makeId),
            'model' => $this->_helper->getLabelById($modelId),
        );

        $collection = Mage::getModel('hautopart/mix')->getCollection();
        $collection->addFieldToFilter('year', $yearId);
        $collection->addFieldToFilter('make', $makeId);
        $collection->addFieldToFilter('model', $modelId);
        $collection->addFieldToFilter('product_id', $params['product_id']);

        $ymmLabel = implode(' ', $ymmLabelArray);

        $ymmLabelArray['model'] = Mage::helper('hautopart')->scrubLabel($ymmLabelArray['model']);
        $fitmentYmmLabel = implode(' ', $ymmLabelArray);
        $ymmLink = $this->_helper->formatUrl($fitmentYmmLabel);  

        $fitmentData = str_replace('-ds','',$ymmLink);
        $_cookie = Mage::getSingleton('core/cookie');
            $_cookie->set('fitment',$fitmentData,1800);

        /** Save user YMM Selection */
        $params['q'] = $fitmentData;
        $params['year'] = $yearId;
        $params['make'] = $makeId;
        $params['model'] = $modelId;
        Mage::helper('hautopart/customer')->setCustomerCombination($params);

        return array(
            'total' => $collection->getSize(),
            'ymm' => $ymmLabel,
            'alternative' => Mage::getBaseUrl() . 'year/' . $fitmentData . '.html'
        );
    }

    protected function _websiteId()
    {
        return  Mage::app()->getStore()->getWebsiteId();
    }

}