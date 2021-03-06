<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 08/03/2017
 * Time: 8:28 PM
 */

class Homebase_Autopart_Block_Adminhtml_Product_Edit_Tab_Combination_Items_Entries extends Mage_Adminhtml_Block_Widget{

    protected $_itemCount = 1;

    protected $_productInstance;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('homebase/product/edit/tab/combination/items/entries2.phtml');
    }

    public function getItemCount()
    {
        return $this->_itemCount;
    }

    public function setItemCount($itemCount)
    {
        $this->_itemCount = max($this->_itemCount, $itemCount);
        return $this;
    }

    public function getFieldName()
    {
        return 'product[combinations]';
    }

    public function getFieldId()
    {
        return 'product_combination';
    }

    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('hfitment/widget_button')
                ->setData(array(
                    'label' => Mage::helper('catalog')->__('Delete Fitment'),
                    'class' => 'delete delete-product-combination delete-fitment-combination',
                )));
        $this->setChild('select_option_type',$this->getLayout()->createBlock(
            'adminhtml/catalog_product_edit_tab_options_type_select'
        ));

        return parent::_prepareLayout(); // TODO: Change the autogenerated stub
    }

    public function getAddButtonId()
    {
        $buttonId = $this->getLayout()
            ->getBlock('admin.combination.options')
            ->getChild('add_button')->getId();
        return $buttonId;
    }
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    public function getTemplatesHtml(){
        return $this->getChildHtml('select_option_type');
    }

    public function isReadonly()
    {
        return false;
    }

    public function getYearSelectHtml(){
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData(array(
                'id' => $this->getFieldId().'_{{id}}_year',
                'class' => 'select select-product-combination-year select-fitment',
                'extra_params' => 'data-comp=y'
            ))
            ->setOptions(Mage::getSingleton('hautopart/option_year')->toOptionArray());
        return $select->getHtml();
    }
    public function getMakeSelectHtml(){
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData(array(
                'id' => $this->getFieldId().'_{{id}}_make',
                'class' => 'select select-product-combination-make select-fitment',
                'extra_params' => 'data-comp=m'
            ))
            ->setOptions(Mage::getSingleton('hautopart/option_make')->toOptionArray());
        return $select->getHtml();
    }
    public function getModelSelectHtml(){
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData(array(
                'id' => $this->getFieldId().'_{{id}}_model',
                'class' => 'select select-product-combination-model select-fitment validate-fitment-combination',
                'extra_params' => 'data-comp=ml',
            ))
            ->setOptions(Mage::getSingleton('hautopart/option_model')->toOptionArray());

        return $select->getHtml();
    }

    public function setProduct($product){
        $this->_productInstance = $product;
    }
    public function getProduct(){
        if (!$this->_productInstance) {
            if ($product = Mage::registry('combination_product')) {
                $this->_productInstance = $product;
            } else {
                $this->_productInstance = Mage::getSingleton('catalog/product');
            }
        }
        return $this->_productInstance;
    }
    public function getFitmentValues(){
        $id = $this->getProduct()->getId();
        $values = array();
        if($id){
            /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
            $_mixes = Mage::getModel('hautopart/mix')->getCollection()
                ->addFieldToFilter('product_id', $id);
            if(count($_mixes) > 0 ){
                /** @var Homebase_Autopart_Model_Resource_Mix_Collection $count */
                $count = Mage::getModel('hautopart/mix')->getCollection()
                    ->addFieldToFilter('product_id',$id);
                $index = 1;
                /** @var Homebase_Autopart_Model_Mix $mix */
                foreach($_mixes as $mix){
                    $value = array();
                    $serial = array(
                        'y' => $mix->getYear(),
                        'm' => $mix->getMake(),
                        'ml' => $mix->getModel(),
                        'id' => $mix->getId(),
                        'i' => $index
                    );
                    $value['id'] =  $index;
                    $value['serial'] = json_encode($serial);
                    $value['item_count'] = $index;
                    $values[] = new Varien_Object($value);
                    $index++;
                }
            }
        }
        return $values;
    }
    public function getFitmentCombinations(){
        $id = $this->getProduct()->getId();
        $values = array();
        if($id){
            /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
            $_mixes = Mage::getModel('hautopart/mix')->getCollection()
                ->addFieldToFilter('product_id', $id);
            if(count($_mixes) > 0 ){
                /** @var Homebase_Autopart_Model_Resource_Mix_Collection $count */
                $count = Mage::getModel('hautopart/mix')->getCollection()
                    ->addFieldToFilter('product_id',$id);
                $index = 1;
                /** @var Homebase_Autopart_Model_Mix $mix */
                foreach($_mixes as $mix){
                    $serial = array(
                        'y' => $mix->getYear(),
                        'm' => $mix->getMake(),
                        'ml' => $mix->getModel(),
                        'id' => $mix->getId(),
                        'i' => $index
                    );
                    $values[] = array(
                        'id' => $index,
                        'serial' => $serial
                    );
                    $index++;
                }
            }
        }
        return json_encode($values);
    }
    public function getCombinationValues(){
        $id = $this->getProduct()->getId();
        $values = array();
        if($id){
            /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_mixes */
            $_mixes = Mage::getModel('hautopart/mix')->getCollection()
                ->addFieldToFilter('product_id', $id);
            if(count($_mixes) > 0 ){
                /** @var Homebase_Autopart_Model_Resource_Mix_Collection $count */
                $count = Mage::getModel('hautopart/mix')->getCollection()
                    ->addFieldToFilter('product_id',$id);
                $res = $count->addOrder('position',Homebase_Autopart_Model_Resource_Mix_Collection::SORT_ORDER_DESC)
                    ->fetchItem();
                $itemCount = 1;
                if($res->getId()){
                    $itemCount = count($res);
                }

                $index = 1;
                /** @var Homebase_Autopart_Model_Mix $mix */
                foreach($_mixes as $mix){
                    $value = array();
                    $value['id'] = $index;
                    $value['year']  = $mix->getYear();
                    $value['make'] = $mix->getMake();
                    $value['model'] = $mix->getModel();
                    $value['recid'] = $mix->getId();
                    $value['serial'] = implode('-',$mix->toArray(array('year','make','model')));
                    $value['item_count'] = $index;
                    $values[] = new Varien_Object($value);
                    $index++;
                }
            }
        }
        return $values;
    }
}