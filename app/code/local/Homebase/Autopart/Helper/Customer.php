<?php

class Homebase_Autopart_Helper_Customer extends Mage_Core_Helper_Abstract {

    /**
     * @param array $request
     * @param null $customerEmail
     * @throws Mage_Core_Model_Store_Exception
     */
    public function setCustomerCombination($request = array(), $customerEmail = null)
    {
        $customerId = 0;
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerSession = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customerSession->getId();
            $customerEmail = $customerSession->getEmail();
        }

        if(!empty($customerEmail)){
            $storeId = Mage::app()->getStore()->getStoreId();
            $year = (int) $request['year'];
            $make = (int) $request['make'];
            $model = (int) $request['model'];

            if(!empty($year) && !empty($make) && !empty($model)) {

                $var = array('request' => $request['q']);
                $ymmId = $this->_checkCustomerYmm($request, $customerEmail, $storeId);

                $ymmModel = Mage::getModel('hautopart/customer');
                if($ymmId){
                    $ymmModel->load($ymmId);
                }

                $createdAt = Mage::getModel('core/date')->timestamp();
                $ymmModel->setCustomerId($customerId)
                    ->setCustomerEmail($customerEmail)
                    ->setYear($year)
                    ->setMake($make)
                    ->setModel($model)
                    ->setStoreId($storeId)
                    ->setIsSync(0)
                    ->setVar(serialize($var))
                    ->setCreatedAt($createdAt)
                    ->save(); 
            }
        }

        return;
    }

    /**
     * @param $request
     * @param $customerEmail
     * @param $storeId
     * @return mixed
     */
    protected function _checkCustomerYmm($request, $customerEmail, $storeId)
    {
        $collection = Mage::getModel('hautopart/customer')->getCollection()
            ->addFieldToSelect('id')
            ->addFieldToFilter('customer_email', $customerEmail)
            ->addFieldToFilter('store_id', $storeId);

        if($collection->getSize() > 0){
            foreach ($collection as $item){
                return $item->getId();
            }
        }

        return;
    }

    /**
     * Customer login
     */
    public function customerLoginCombination()
    {
        $_cookie = Mage::getSingleton('core/cookie');
        $fitment = $_cookie->get('fitment');
        $fitmentYmm = $_cookie->get('fitment-ymm');

        if(!empty($fitment) && !empty($fitmentYmm)) {
            $ymm = explode('-', $fitmentYmm);
            $request = array(
                'q' => $fitment,
                'year' => $ymm[0],
                'make' => $ymm[1],
                'model' => $ymm[2]
            );
            $this->setCustomerCombination($request);
        }else{
            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                $storeId = Mage::app()->getStore()->getStoreId();
                $customerSession = Mage::getSingleton('customer/session')->getCustomer();
                $customerId = $customerSession->getId();
                $customerYmm = Mage::getModel('hautopart/customer')->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('store_id', $storeId)
                    ->setOrder('created_at','DESC')
                    ->setPageSize('1');

                if($customerYmm->getSelect()){
                    foreach ($customerYmm as $item){

                        $var = unserialize($item->getData('var'));
                        $request = $var['request'];
                        $ymm = "{$item->getData('year')}-{$item->getData('make')}-{$item->getData('model')}";

                        $_cookie = Mage::getSingleton('core/cookie');
                        $_cookie->set('fitment',$request,1800);
                        $_cookie->set('fitment-ymm',$ymm,1800);
                    }
                }
            }
        }
    }

    /**
     * Get customer session ID
     * @return string
     */
    protected function _getCustomerId()
    {
        $sessionId = 0;
        if(Mage::getSingleton('customer/session')->isLoggedIn()){
            $sessionId = Mage::getSingleton('customer/session')->getId();
        }

        return $sessionId;
    }

    /**
     * @return |null
     */
    public function getSingleMake()
    {
        $make = null;
        $makeIds = Mage::getStoreConfig('fitment/configuration/make');
        $makeArray = explode(',', $makeIds);

        if(count($makeArray) == 1){
            $make = $makeArray[0];
        }

       return $make;
    }

}

