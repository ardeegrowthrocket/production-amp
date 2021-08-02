<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 3/20/17
 * Time: 11:34 PM
 */

class Homebase_Autopart_IndexController extends Mage_Core_Controller_Front_Action {

    protected $_enableFitmentMaker;

    protected $_fitmentMaker;

    protected function _construct()
    {
        $this->_enableFitmentMaker = $configValue = Mage::getStoreConfig('fitment/configuration/enable');
        $this->_fitmentMaker = $configValue = Mage::getStoreConfig('fitment/configuration/make');
        parent::_construct();
    }


    public function indexAction(){
        $_params = $this->getRequest()->getParams();
        //Zend_Debug::dump($_params);

        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $bannedWord = array('core_config_data','update','select');
        $columnValidation = array('year','make','model');

        foreach ($bannedWord as $word){
            if(strpos(strtolower($currentUrl), $word) !== false){
                $this->_invalidRequest();
            }
        }

        if(!in_array($_params['column'], $columnValidation)){
            $this->_invalidRequest();
        }

        $response = array();
        if(is_array($_params)){
            if(array_key_exists('condition',$_params)){
                $websiteid = Mage::app()->getStore()->getWebsiteId();
                $condition = json_decode($_params['condition']);
                $collection = Mage::getModel('hautopart/combination')->getCollection();
                $collection->getSelect()->join(array($_params['column'] => 'auto_combination_list_labels'),' '.$_params['column'].'='. $_params['column'] .'.option',
                array('llabel' => 'label', 'name' => 'name'))
                    ->where('main_table.store_id = ?',$websiteid);
                foreach($condition as $ndx=> $value) {
                    $collection->addFieldToFilter($ndx, $value);
                }

                if($this->_enableFitmentMaker && !empty($this->_fitmentMaker)){
                    $collection->addFieldToFilter('make',array('in' => explode(',',$this->_fitmentMaker)));
                }

                $collection->addOrder('label','ASC');
                $collection->getSelect()->group('label');

                foreach($collection as $item) {
                    $response[] = array(
                        'id' => $item->getData($_params['column']),
                        'label' => $item->getLlabel(),
                        'name' => $item->getName(),
                        'website_id' => $websiteid
                    );
                }
            }
        }
        $this->getResponse()->setHeader('Content-type','application/json');
        $this->getResponse()->setBody(json_encode($response));
    }

    public function queryAction(){

        if (!$this->_validateFormKey()) {
            $this->_redirectReferer();
            return;
        }

        $params = $this->getRequest()->getParams();
        if(empty($params['make'])){
            $make = Mage::helper('hautopart/customer')->getSingleMake();
            $params['make'] = $make;
        }

        /** Save user YMM Selection */
        Mage::helper('hautopart/customer')->setCustomerCombination($params);

        $yq = $this->getRequest()->getParam('q');
        $ymm = "{$params['year']}-{$params['make']}-{$params['model']}";
        $baseUrl = Mage::getBaseUrl() . 'year/' . $yq . '.html';
        /** @var Mage_Core_Model_Cookie $_cookie */
        $_cookie = Mage::getSingleton('core/cookie');
        $_cookie->set('fitment',$yq,1800);
        $_cookie->set('fitment-ymm',$ymm,1800);
        $this->_redirectUrl($baseUrl);
    }

    public function resetAction(){
        $_session =  Mage::getSingleton('core/session');
        $_cookie = Mage::getSingleton('core/cookie');
        $_cookie->delete('fitment');
        $this->_redirectUrl(Mage::getBaseUrl());
    }

    /**
     * Ajax get YMM interlink
     * @throws Mage_Core_Model_Store_Exception
     */
    public function interlinkAction()
    {

        if (!$this->_validateFormKey()) {
            $this->_redirectReferer();
            return;
        }

        $request = $this->getRequest()->getParams();
        $yearId = (int) $request['year'];
        $makeId = (int) $request['make'];
        $modelId = (int) $request['model'];

        $title = "";
        $ymmData = array();
        $request = $this->getRequest()->getParams();
        $resource = Mage::getSingleton('core/resource');
        $_store = Mage::app()->getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('part_name')
            ->addAttributeTofilter('status', 1)
            ->addWebsiteFilter($_store->getWebsite());

        $collection->getSelect()->joinLeft(
            array("auto" => $resource->getTableName('hautopart/combination_list')),
            "e.entity_id = auto.product_id",array("auto_model" => "auto.model", "auto_year" => "auto.year", "auto_make" => "auto.make"));
        $collection->getSelect()->joinLeft(
            array("auto_model" => 'auto_combination_list_labels'),
            "auto_model.option = auto.model",
            array("auto_model_label" => "auto_model.label"));
        $collection->getSelect()->joinLeft(
            array("auto_make" => 'auto_combination_list_labels'),
            "auto_make.option = auto.make",
            array("auto_make_label" => "auto_make.label"));
        $collection->getSelect()->joinLeft(
            array("auto_year" => 'auto_combination_list_labels'),
            "auto_year.option = auto.year",
            array("auto_year_label" => "auto_year.label"));

        $collection->getSelect()->where("auto.make={$makeId}");
        $collection->getSelect()->where("auto.model={$modelId}");
        $collection->getSelect()->where("auto.year !={$yearId}");
        $collection->getSelect()->columns(array('entity_id' => new Zend_Db_Expr('auto.id'),));
        $collection->getSelect()->order('auto_year_label desc');
        $baseUrl = Mage::getBaseUrl();
         if(!empty($collection)) {
             foreach ($collection as $product){

                 $year = $product->getAutoYearLabel();
                 $make = $product->getAutoMakeLabel();
                 $model = $product->getAutoModelLabel();

                 if($model == '1500') {
                     $model = '1500 DS';
                 }

                 $ymmCombination = array();
                 $ymmCombination[0] =  $year;
                 $ymmCombination[1] =  ucwords($make);
                 $ymmCombination[2] =  ucwords($model);

                 $link = Mage::helper('hauto/path')->filterTextToUrl(implode(' ',$ymmCombination));
                 $ymmData['title'] = implode(' ', array($make,$model));

                 $ymmData['interlink'][$link] = array(
                     'label' => implode(' ', $ymmCombination),
                     'link' => $baseUrl . "year/{$link}.html",
                 );
             }
         }
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($ymmData));
    }

    /**
     * Return invalid request
     */
    protected function _invalidRequest()
    {
        header('HTTP/1.0 403 Forbidden');
        exit;
    }
}
