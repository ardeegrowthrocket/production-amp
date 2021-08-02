<?php
class Growthrocket_Syncymm_Model_Sync extends Mage_Core_Model_Abstract {


    protected $_isEnableSync;

    protected $_authUsername;

    protected $_authPassword;

    protected $_syncLimit;

    protected $_syncUrl;

    protected $_helper;

    protected $_collection;

    protected $_forSyncRecord = array();

    protected $_ymmLabel = array();

    const DD_CONTACTS_API = '/v2/contacts/';

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_helper = Mage::helper('gr_syncymm');
        $this->_isEnableSync = $this->_helper->isEnableSync();
        $this->_authUsername = $this->_helper->getAuthUsername();
        $this->_authPassword = $this->_helper->getAuthPassword();
        $this->_syncLimit = $this->_helper->getSyncLimit();
        $this->_syncUrl = $this->_helper->getSyncUrl();

        parent::_construct();
    }

    /**
     * @return array
     */
    public function getYmmCombination()
    {
        $this->_getYmmLabel();
        $this->_collection =  Mage::getModel('hautopart/customer')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('is_sync', 0)
            ->addFieldToFilter('year', array('neq' => 0 ))
            ->addFieldToFilter('make',  array('neq' => 0 ))
            ->addFieldToFilter('model',  array('neq' => 0 ))
            ->addFieldToFilter('created_at', array(
                'from'     => strtotime('-5 day', time()),
                'to'       => time(),
                'datetime' => true
            ))
            ->setPageSize($this->_syncLimit)
            ->setOrder('created_at','ASC');

        foreach ($this->_collection as $item){

            $year = isset($this->_ymmLabel[$item->getYear()]) ? $this->_ymmLabel[$item->getYear()] : '';
            $make = isset($this->_ymmLabel[$item->getMake()]) ? $this->_ymmLabel[$item->getMake()] : '';
            $model = isset($this->_ymmLabel[$item->getModel()]) ? $this->_ymmLabel[$item->getModel()] : '';

            $this->_forSyncRecord[] = array(
                'id' => $item->getId(),
                'email' => $item->getCustomerEmail(),
                'year' => $year,
                'make' => ucfirst($make),
                'model' => ucfirst($model)
            );
        }

        return  $this->_forSyncRecord;
    }

    /**
     * @return array
     */
    protected function _getYmmLabel()
    {
        $collection = Mage::getModel('hautopart/label')->getCollection()
                ->addFieldToSelect('*');
        foreach ($collection as $item){
            $this->_ymmLabel[$item->getOption()] = $item->getName();
        }
        return $this->_ymmLabel;
    }

    /**
     * Sync to DotDigital
     * @throws Exception
     */
    public function sync()
    {
        if(!$this->_isEnableSync){
            return;
        }

        $this->getYmmCombination();
        if(!empty($this->_forSyncRecord)){
            foreach ($this->_forSyncRecord as $item){
                $syncData = array();
                $response = $this->_getContactIdByEmail($item['email']);
                $contactId = isset($response['id']) ? $response['id'] : null;
                $optInType = isset($response['optInType']) ? $response['optInType'] : null;

                if(!empty($contactId)){
                    $syncData = array(
                        "email" => $item['email'],
                        "optInType" => $optInType,
                        "dataFields" => array(
                            array("Key" => "YEAR", "value" => $item['year']),
                            array("Key" => "MAKE", "value" => $item['make']),
                            array("Key" => "MODEL", "value" => $item['model'])
                        )
                    );

                    $responseUpdate = $this->_updateYmmContactsById($contactId, $syncData);
                    if($responseUpdate){
                        $ymmModel = Mage::getModel('hautopart/customer');
                        $ymmModel->load($item['id']);
                        $ymmModel->setIsSync(1);
                        $ymmModel->save();

                        Mage::log('Sync: ' . $item['email'], null, 'dd_ymm_sync.log', true);
                    }
                }
                sleep(5); //add delay
            }
        }
    }

    /**
     * @param $email
     * @return mixed
     */
    protected function _getContactIdByEmail($email)
    {
        $url = $this->_syncUrl . "/v2/contacts/" . $email;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_authUsername . ':' . $this->_authPassword);

        return json_decode(curl_exec($ch), true);
    }

    /**
     * @param $contactId
     * @param $syncData
     * @return mixed
     */
    protected function _updateYmmContactsById($contactId, $syncData)
    {
        $url = $this->_syncUrl . "/v2/contacts/" . $contactId;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_authUsername . ':' . $this->_authPassword);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($syncData));

        return json_decode(curl_exec($ch), true);
    }

}