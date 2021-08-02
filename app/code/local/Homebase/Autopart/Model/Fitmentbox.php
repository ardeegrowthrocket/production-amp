<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 03/08/2018
 * Time: 3:42 PM
 */

class Homebase_Autopart_Model_Fitmentbox{
    const GLOBAL_WEBSITE_ID = 0;
    public function build(){
        $time_start = microtime(true);
        $_helper = Mage::helper('hautopart');
        $resource = $this->_getResource();
        $reader = $this->_getReader();
        $writer = $this->_getWriter();
        $websiteTable = $resource->getTableName('core/website');
        $productWebsiteTable = $resource->getTableName('catalog/product_website');
        $fitmentTable = $resource->getTableName('hautopart/combination_list');
        $fitmentboxTable = $resource->getTableName('hautopart/combination');
        $select = $reader->select()
            ->from(array('w' => $websiteTable),array('website_id','code','name'))
            ->join(array('p' => $productWebsiteTable),'p.website_id=w.website_id')
            ->join(array('f' => $fitmentTable),'p.product_id= f.product_id',array('year','make','model'))
            ->where('w.website_id > ?', self::GLOBAL_WEBSITE_ID)
            ->group(array('p.website_id','p.product_id','f.year','f.make','f.model'));
        $statement = $select->query();
        $counter = 0;

        /** @var Homebase_Autopart_Model_Resource_Combination $_combinationResource */
        $_combinationResource = Mage::getModel('hautopart/combination')->getResource();
        $_combinationResource->resetAutoIncrement();
        while($result = $statement->fetch()){
            /** @var Mage_Core_Model_Website $_website */
            $_website = Mage::getModel('core/website')->load($result['website_id']);
            $_store = $_website->getDefaultStore();

            if(!Mage::getStoreConfig('fitment/configuration/enable', $_store)){
                $writer->insertIgnore($fitmentboxTable,array(
                    'year' => $result['year'],
                    'make' => $result['make'],
                    'model' => $result['model'],
                    'store_id' => $result['website_id']
                ));
            }else{
                $allowedMakes = explode(',',Mage::getStoreConfig('fitment/configuration/make', $_store));
                if(in_array($result['make'],$allowedMakes)){
                    $writer->insertIgnore($fitmentboxTable,array(
                        'year' => $result['year'],
                        'make' => $result['make'],
                        'model' => $result['model'],
                        'store_id' => $result['website_id']
                    ));
                }
            }
            $counter++;
        }

        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start)/60;

        //Build Labels
        /** @var Homebase_Autopart_Model_Resource_Label $_labelResource */
        $_labelResource = Mage::getModel('hautopart/label')->getResource();
        $_labelResource->resetAutoIncrement();

        $statement = $reader->select()->from($fitmentTable,array('year','make','model'))->query();

        while($fitment = $statement->fetch()){
            foreach($fitment as $property){
                $_label = Mage::getModel('hautopart/label')->load($property,'option');
                try{
                    if(!$_label->getId()){

                        $label = $_helper->getOptionValue($property);
                        $name = $label;
                        if($property == 311) {
                            $name = '1500 DS';
                        }

                        $_label->setOption($property);
                        $_label->setLabel($label);
                        $_label->setName($name);
                        $_label->save();
                    }
                }catch(Exception $exception){
                    Mage::log($exception->getMessage(),null,'townfix.log');
                }
            }
        }
        /** Build auto_type to auto_label */
        Mage::helper('hautopart')->buildAutoTypeLabel();

        $event = Mage::helper('grevent');
        $event->completeYmmComboEvent();
    }



    private function isUniqueFitmentBoxRecord($params){
        $reader = $this->_getReader();
        $resource = $this->_getResource();
        $fitmentTable = $resource->getTableName('hautopart/combination');
        $allowedKeys = array('year','make','model','store_id');

        $select = $reader->select()
            ->from($fitmentTable);

        foreach($params as $key => $value){
            if(in_array($key,$allowedKeys)){
                $select->where($key . ' = ?', $value);
            }
        }
        $stmt = $select->query();
        return $stmt->rowCount() == 0;
    }
    protected function fetchFitment($productId, $params = array()){
        $reader = $this->_getReader();
        $resource = $this->_getResource();
        $fitmentTable = $resource->getTableName('hautopart/combination_list');
        $result = array();
        if(is_array($params) && empty($params)){
            //Return all fitment
            $statement = $reader->select()
                ->from(array('f' => $fitmentTable),array('year','make','model'))
                ->where('f.product_id =? ', $productId)
                ->query();
            $result = $statement->fetchAll();
        }else{
            $select = $reader->select()
                ->from(array('f' => $fitmentTable),array('year','make','model'))
                ->where('f.product_id =? ', $productId);

            foreach($params as $key => $value){
                $select->where($key . ' IN (?)', explode(',',$value));
            }
            $select->query();
            $statement = $select->query();
            $result = $statement->fetchAll();
        }
        return $result;
    }

    /**
     * @return Magento_Db_Adapter_Pdo_Mysql
     */
    private function _getReader(){
        $resource = $this->_getResource();
        return $resource->getConnection('core_read');
    }

    /**
     * @return Magento_Db_Adapter_Pdo_Mysql
     */
    private function _getWriter(){
        $resource = $this->_getResource();
        return $resource->getConnection('core_write');
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    private function _getResource(){
        return Mage::getSingleton('core/resource');
    }
}