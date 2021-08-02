<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/17
 * Time: 9:08 PM
 */

class Homebase_Utility_Helper_Data extends Mage_Core_Helper_Abstract {
    const SPECIAL_PRICE_ATTRIBUTE_ID = 76;
    const SPECIAL_PRICE_FROM_DATE_ATTRIBUTE_ID = 77;
    const SPECIAL_PRICE_TO_DATE_ATTRIBUTE_ID =78;
    public function hasActiveFitmentQuery(){
        /** @var Mage_Core_Model_Cookie $_cookie */
        $_cookie = Mage::getSingleton('core/cookie');
        return $_cookie->get('fitment') !== false;
    }
    public function hasActiveSpecialPricing($productId){

        /** @var Mage_Core_Model_Resource_Config $eavConn */
        $eavConn = Mage::getResourceModel('core/config');
        /** @var Mage_Core_Model_Resource $conn */
        $conn = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $conn->getConnection('core_read');

        $decimalAttrTable = $eavConn->getValueTable('catalog/product','decimal');
        $dateAttrTable = $eavConn->getValueTable('catalog/product','datetime');
        $entity = $eavConn->getTable('catalog/product');

        //Fetch Store Specific Special Price
        $select =$reader->select()
            ->from(array('p' => $entity))
            ->joinLeft(array('sp' => $decimalAttrTable), 'p.entity_id=sp.entity_id',array('special_price' => 'value'))
            ->joinLeft(array('from' => $dateAttrTable),'p.entity_id=from.entity_id',array('from' => 'value'))
            ->joinLeft(array('to' => $dateAttrTable),'p.entity_id=to.entity_id', array('to' => 'value'))
            ->where('p.entity_id = ?', $productId)
            ->where('sp.attribute_id = ? OR ISNULL(sp.attribute_id)',self::SPECIAL_PRICE_ATTRIBUTE_ID)
            ->where('from.attribute_id = ? OR ISNULL(from.attribute_id)' , self::SPECIAL_PRICE_FROM_DATE_ATTRIBUTE_ID)
            ->where('to.attribute_id = ? OR ISNULL(to.attribute_id)',self::SPECIAL_PRICE_TO_DATE_ATTRIBUTE_ID)
            ->where('sp.store_id = ?', Mage::app()->getStore()->getId());
        $result = $select->query();
        $results = $result->fetchAll();

        //Check if there's a store specific specila price value
        if(count($results) == 0){
            //Use Default Value
            $select =$reader->select()
                ->from(array('p' => $entity))
                ->joinLeft(array('sp' => $decimalAttrTable), 'p.entity_id=sp.entity_id',array('special_price' => 'value'))
                ->joinLeft(array('from' => $dateAttrTable),'p.entity_id=from.entity_id',array('from' => 'value'))
                ->joinLeft(array('to' => $dateAttrTable),'p.entity_id=to.entity_id', array('to' => 'value'))
                ->where('p.entity_id = ?', $productId)
                ->where('sp.attribute_id = ? OR ISNULL(sp.attribute_id)',self::SPECIAL_PRICE_ATTRIBUTE_ID)
                ->where('from.attribute_id = ? OR ISNULL(from.attribute_id)' , self::SPECIAL_PRICE_FROM_DATE_ATTRIBUTE_ID)
                ->where('to.attribute_id = ? OR ISNULL(to.attribute_id)',self::SPECIAL_PRICE_TO_DATE_ATTRIBUTE_ID)
                ->where('sp.store_id = ?', 0);
            $result = $select->query();
            $results = $result->fetchAll();
        }
        $record = array_pop($results);

        $specialPrice = $record['special_price'];
        $dateFrom = is_null($record['from']) ? null : date_create($record['from']);
        $dateTo = is_null($record['to']) ? null : date_create($record['to']);
        $today = date_create(now());
        if(is_null($specialPrice)){
            return false;
        }

        if(is_null($dateFrom) && is_null($dateTo)){
            if(!is_null($specialPrice)){
                return true;
            }else{
                return false;
            }
        }else{
            if(is_null($dateFrom) && !is_null($dateTo)){
                $dateDiffTo = date_diff($today, $dateTo);
                if(!$dateDiffTo->invert){
//                    echo "FROM Not set only Date To";
                    return true;
                }else{
                    //echo "FROM Not set only Date To but already passed";
                    return false;
                }
            }else if(is_null($dateTo) && !is_null($dateFrom)){
                $dateDiffFrom = date_diff($today,$dateFrom);
                if($dateDiffFrom->invert){
                    //Zend_Debug::dump("To not set but from is valid");
                    return true;
                }else{
                    //Zend_Debug::dump("To not set but from is in the future");
                    return false;
                }
            }else{
                $dateDiffFrom = date_diff($today,$dateFrom);
                $dateDiffTo = date_diff($today, $dateTo);
                if($dateDiffFrom->invert && !$dateDiffTo->invert){
//                    Zend_Debug::dump("From date is lesser than today but To date is greater");
                    return true;
                }else{
                    return false;
                }
            }
        }
    }
}