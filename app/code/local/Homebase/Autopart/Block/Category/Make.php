<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 4/2/17
 * Time: 6:56 PM
 */

class Homebase_Autopart_Block_Category_Make extends Mage_Core_Block_Template implements Homebase_Autopart_Block_Category_CategoryInterface{

    protected $_helper;

    protected $_collection;

    public function _construct(){
        parent::_construct();
        $this->_helper = Mage::helper('hautopart/parser');
    }
    protected function _prepareLayout(){
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $size = count($params);
        $ctr = 0;
        if($breadcrumbs){
            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));

            foreach($params as $ndx=> $value){
                $ctr++;
                $label = $this->_helper->getLabel($value);
                $breadcrumbs->addCrumb('ymm-' . $ndx, array(
                    'label' => ucwords($label),
                    'title' => $label,
                    'link'  => (($ctr < $size) ? $this->_helper->getLink($label,$ndx) : '')
                ));
            }
        }

        $this->getList();
        $this->getCustomModelMeta();

        return parent::_prepareLayout();
    }
    public function getList()
    {

        if(!$this->_collection) {

            // TODO: Implement getList() method.

            $params = unserialize($this->getRequest()->getParam('ymm_params'));
            if(empty($params)){
                return;
            }
            /** @var Homebase_Autopart_Model_Resource_Combination_Collection $collection */
            $collection = Mage::getModel('hautopart/combination')->getCollection();

            $collection->join(array('model' => 'hautopart/combination_label'),'model=model.option');

            $websiteId = Mage::app()->getWebsite()->getId();
            $collection->getSelect()->where('main_table.store_id = '. $websiteId);
            foreach($params as $ndx => $value){
                $collection->addFieldToFilter($ndx, $value);
                $collection->getSelect()->group('model');
                $collection->addOrder('label','ASC');

                $this->_collection =  $collection;
                return;
            }
        }

        return $this->_collection;

    }


    public function getCustomModelMeta()
    {
        $data = array();
        $counter = 1;
        $collection = $this->_collection;
        if($this->_collection) {
            foreach ($collection as $item){

                if($counter <= 3) {
                    $data[] = $item->getLabel();
                    $counter++;
                }else{
                    break;
                }
            }
        }
        Mage::register('make_model_meta', $data);
    }

    public function getAutoName($prefix = true)
    {
        // TODO: Implement getAutoName() method.
        $params = unserialize($this->getRequest()->getParam('ymm_params'));
        $ymm = array();
        foreach($params as $key => $param){
            $ymm[] = $this->_helper->getLabel($param);
        }

        if($prefix){
            $ymm[] = 'Parts & Accessories';
        }

        return implode(' ', $ymm);
    }

    public function getCustomLink($model){
        return $this->_helper->getLink($model,'model');
    }

    public function getImage($optionId, $isResize = false, $width = 300, $height = 300){

        $_image = Mage::getModel('hautopart/image')->load($optionId,'option_id');
        if(!$isResize){
            return Mage::getBaseUrl('media') . 'hautopart' . $_image->getImgPath();
        }else{
           return  Mage::helper('hautopart/image')->reSize($_image->getImgPath(), $width, $height);
        }
    }
}
