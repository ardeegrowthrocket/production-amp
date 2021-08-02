<?php
class Growthrocket_Redirection_Model_Observer
{

    protected $allowed_paths = array();

    public function redirection(Varien_Event_Observer $observer)
    {
        $action = Mage::app()->getRequest()->getActionName();
        $storeCode =  Mage::helper('gr_redirection')->getStoreCode();

        /** SOP  */
        if($storeCode == 'sop') {
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $this->_productRedirect($currentUrl);
            $this->_repairPartsRedirect($currentUrl);
            $this->_redirectToCategory($currentUrl);
            $this->_staticRedirect($currentUrl, $storeCode);
            $this->_redirectNonHttps();
        }

        /** MOP  */
        if($storeCode == 'mop') {
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $this->_repairPartsRedirectMOP($currentUrl);
            $this->_staticRedirect($currentUrl, $storeCode);
        }
    }

    public function MainRedirection()
    {
        $storeCode =  Mage::helper('gr_redirection')->getStoreCode();
        /** MOP */
        if($storeCode == 'mop') {
            $path = trim(Mage::app()->getRequest()->getPathInfo(), '/');
            if($path){
                $p = explode('/', $path);
                if(isset($p[0]) && !in_array($p[0], $this->_allowedPath())){
                    $currentUrl = Mage::helper('core/url')->getCurrentUrl();
                    $this->_productRedirectMOP($currentUrl);
                    $this->_redirectToCategory($currentUrl);
                }
            }

            $this->_redirectNonHttps();
        }
    }

    /**
     * Redirect non HTTPS and Non WWW
     */
    protected function _redirectNonHttps()
    {
        $baseUrl =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        if (substr($_SERVER['HTTP_HOST'], 0, 4) !== 'www.' && strpos($baseUrl, 'https://www.') !== false) {
            $this->_redirect('https://www.'.$_SERVER['HTTP_HOST'].'/'. trim(strtolower($_SERVER['REQUEST_URI']), '/'));
            exit;
        }elseif(strpos($currentUrl, 'http://') !== false && strpos($baseUrl, 'https//') !== false){
            $this->_redirect('https://'.$_SERVER['HTTP_HOST'].'/'. trim(strtolower($_SERVER['REQUEST_URI']), '/'));
            exit;
        }
    }

    /**
     * @param $currentUrl
     */
    protected function _staticRedirect($currentUrl, $storeCode)
    {
        $customUrl = array(
            'faq.php'  => 'faq',
            'conditions.php' => 'conditions-of-use',
            'privacy.php' => 'privacy-notice',
            'links.php' => 'friends-of-sop',
            'create_account.php' => 'customer/account/create',
            'login.php' => 'customer/account/login',
            'account.php' => 'customer/account/index/',
            'account_history.php' => 'sales/order/history',
            'contact_us.php' => 'contact-us',
            'account_notifications.php' => 'customer/account/login',
            'reward_dollars.php' => 'reward-dollars',
        );

        if($storeCode == 'sop'){
            $customUrl = array_merge($customUrl, [
                    'products_new.php' => 'make/subaru.html',
                    'specials.php' => 'make/subaru.html',
                    'reviews.php' => 'make/subaru.html',
                    'subaruonlineparts.com/-c-160_170.html' => 'make/subaru.html',
                    '/mobile' => 'make/subaru.html',
                ]);
        }

        if($storeCode == 'mop'){
            $customUrl = array_merge($customUrl, [
                'products_new.php' => '',
                'specials.php' => '',
                'reviews.php' => '',
                '/mobile' => '',
            ]);
        }


        foreach ($customUrl as $oldUrl => $newUrl){
            if(strpos($currentUrl, $oldUrl) !== false){
                $this->_redirect(Mage::getBaseUrl() .  $newUrl);
            }
        }
    }

    /**
     * Product Redirect MOP
     * @param $currentUrl
     */
    protected function _productRedirectMOP($currentUrl)
    {
        if (preg_match('/-p-(.*?).html/', $currentUrl, $match) == 1) {
            $productId = $match[1];
            if(!empty($productId)){
                $result = Mage::helper('gr_redirection')->getProductPartNumberById($productId);
                if(!empty($result[0])){
                    $_catalog = Mage::getModel('catalog/product');
                    $_productId = $_catalog->getIdBySku($result[0]);

                    if($_productId){
                        $_product = Mage::getModel('catalog/product')->load($_productId);
                        $url = Mage::getBaseUrl() . "sku/{$_product->getCustomUrlKey()}.html";
                        $this->_redirect(strtolower($url));
                    }
                }
            }
        }
    }

    /**
     * Product Redirect
     * @param $currentUrl
     */
    protected function _productRedirect($currentUrl)
    {
        if (preg_match('/-p-(.*?).html/', $currentUrl, $match) == 1) {
             $productId = $match[1];
             if(!empty($productId)){
                 $result = Mage::helper('gr_redirection')->getProductPartNumberById($productId);
                 if(!empty($result[0])){
                     $_catalog = Mage::getModel('catalog/product');
                     $_productId = $_catalog->getIdBySku($result[0]);

                     if($_productId){
                         $_product = Mage::getModel('catalog/product')->load($_productId);
                         $url = Mage::getBaseUrl() . "sku/{$_product->getCustomUrlKey()}.html";
                         $this->_redirect(strtolower($url));
                     }else{
                         $sliceUrl = explode('/', $currentUrl);
                         $modelArray = Mage::helper('gr_redirection')->getModelToArray();
                         if(isset($sliceUrl[3])){
                             $slicePathUrl = explode('-', $sliceUrl[3]);
                             $secondString = strtolower($slicePathUrl[1]);
                             if(array_key_exists($secondString, $modelArray)){
                                 $url = Mage::getBaseUrl() . "model/subaru-{$modelArray[$secondString]}.html";
                             }elseif($secodString = 'subaru'){
                                 $url = Mage::getBaseUrl() . "make/subaru.html";
                             }else{
                                 $url = Mage::getBaseUrl() . "make/subaru.html";
                             }
                             $this->_redirect(strtolower($url));
                         }
                     }
                 }else{
                     $sliceUrl = explode('/', $currentUrl);
                     if(isset($sliceUrl[3]) && $sliceUrl[3] == 'mobile'){
                         $offset = 4;
                     }else{
                         $offset = 3;
                     }
                     $modelArray = Mage::helper('gr_redirection')->getModelToArray();
                     if(!empty($offset)){
                         $slicePathUrl = explode('-', $sliceUrl[$offset]);
                         $secondString = strtolower($slicePathUrl[1]);
                         if(array_key_exists($secondString, $modelArray)){
                             $url = Mage::getBaseUrl() . "model/subaru-{$modelArray[$secondString]}.html";
                         }elseif($secodString = 'subaru'){
                             $url = Mage::getBaseUrl() . "make/subaru.html";
                         }else{
                             $url = Mage::getBaseUrl() . "make/subaru.html";
                         }
                         $this->_redirect($url);
                     }elseif(!empty($sliceUrl[3])){
                         $url = Mage::getBaseUrl() . "make/subaru.html";
                         $this->_redirect($url);
                     }
                 }

             }
        }
    }

    /**
     * Repair Parts Redirect
     * @param $currentUrl
     */
    protected function _repairPartsRedirect($currentUrl)
    {
        if (preg_match('/-repair-parts-(.*?).html/', $currentUrl, $match) == 1) {
            if(!empty($match[1])){
                $this->_redirect('https://parts.subaruonlineparts.com/v-subaru');
            }
        }
    }

    /**
     * @param $currentUrl
     */
    protected function _repairPartsRedirectMOP($currentUrl)
    {
        if (strpos($currentUrl, '/repair-parts/') !== false) {
            $this->_redirect('https://parts.moparonlineparts.com');
        } 
    }

    /**
     * Redirect Category
     * @param $currentUrl
     */
    protected function _redirectToCategory($currentUrl)
    {
        if (preg_match('/-c-(.*?).html/', $currentUrl, $match) == 1) {
            if(!empty($match[1])){
                $categoryIds = explode('_', $match[1]);
                if(!empty($categoryIds)){
                    $lastId = end($categoryIds);
                    $result = Mage::helper('gr_redirection')->getRedirectUrlFromId($lastId);
                    if(isset($result[0])){
                         $url = Mage::getBaseUrl() . $result[0];
                         $this->_redirect($url);
                    }
                }
            }
        }
    }

    /**
     * Allowed Path
     * @return array
     */
    protected function _allowedPath()
    {
        $this->allowed_paths = array(
            'model',
            'year',
            'make',
            'sku',
            'cat',
            'sku-ymm'
        );

        return $this->allowed_paths;
    }

    /**
     * @param $url
     */
    protected function _redirect($url)
    {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: {$url}");
        exit();
    }

    /**
     * @Deprecated
     * @throws Exception
     */
    protected function customConnection()
    {
        $csv = 'db_sop_categories_description.csv';
        $feedDir = Mage::getBaseDir() . DS . $csv;
        $csv = new Varien_File_Csv();
        $csv->setDelimiter(",");
        $data = $csv->getData($feedDir);
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        foreach ($data as $item){
            $productId = $item[0];
            $productNumber = $item[2];

            if(!empty($productId) && !empty($productNumber)){
                $query = "INSERT INTO `gr_site_redirection` (`object_id`,`type`,`name`) VALUES ('{$productId}','category', '{$productNumber}')";
                $writeConnection->query($query);
            }

        }

    }

}
