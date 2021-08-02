<?php
class Growthrocket_News_IndexController extends Mage_Core_Controller_Front_Action{



    public function indexAction() {

        if($this->getRequest()->isPost() && $this->getRequest()->getPost('email')){

            $session            = Mage::getSingleton('core/session');
            $customerSession    = Mage::getSingleton('customer/session');
            $email              = (string) $this->getRequest()->getPost('email');




        $newletterdata = Mage::getModel('newslettertracker/capture');
        $newletterdata->setReferrer($_SERVER['HTTP_REFERER']);
        $newletterdata->setCurrent(Mage::helper('core/url')->getCurrentUrl());
        $newletterdata->setEmail($email);
        $newletterdata->setIp(Mage::helper('core/http')->getRemoteAddr());

        $valid = 1;

        if(!empty($_SERVER['HTTP_REFERER'])){
                $valid = 0;
                foreach (Mage::app()->getWebsites() as $website) {
                    foreach ($website->getGroups() as $group) {
                        $stores = $group->getStores();
                        foreach ($stores as $store) {
           $vipurl =  Mage::app()->getStore($store->getId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
           $vipurl = str_replace("https://","",$vipurl);
           $vipurl = str_replace("http://","",$vipurl);


                            if (strpos($_SERVER['HTTP_REFERER'],$vipurl) !== false) {
                                $valid = 1;
                            }


                        }
                    }
                }
        }

        if(empty($_SERVER['HTTP_REFERER']) || $valid==0){
            $newletterdata->setActive("invalid");
        }else{
            $newletterdata->setActive("valid");
        }

        $newletterdata->save();


        if(empty($_SERVER['HTTP_REFERER']) || $valid==0){
            Mage::throwException($this->__('Invalid referrer.'));
        }   






            $data = array(
                'msg' => 'Success! You\'ve been signed up!',
            );
            try{

                $helperCaptcha = Mage::helper('growthrocket_gtm/captcha');
                if($helperCaptcha->isEnableCaptchaCategory('newsletter')) {
                    $token = $this->getRequest()->getPost('token');
                    $response =  Mage::helper('core')->jsonDecode($helperCaptcha->verifyCaptcha($token));

                    if(!$response['success'] || $response['score'] < $helperCaptcha->getScoreThreshold()) {
                        Mage::throwException($this->__('Invalid Request'));
                    }
                }


                if (!Zend_Validate::is($email, 'EmailAddress')) {
                    Mage::throwException($this->__('Please enter a valid email address.'));
                }
                if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
                    !$customerSession->isLoggedIn()) {
                    Mage::throwException($this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::helper('customer')->getRegisterUrl()));
                }
                $ownerId = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email)
                    ->getId();

                if(!empty($ownerId))
                {
                    Mage::throwException($this->__('This email address is already assigned to another user.'));
                }

                    
                if ($ownerId !== null && $ownerId != $customerSession->getId()) {
                    Mage::throwException($this->__('This email address is already assigned to another user.'));
                }
                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                    $data['msg'] = 'Confirmation request has been sent.';
                }else{
                    $data['msg'] = 'Thank you for your subscription.';
                }
            }catch(Mage_Core_Exception $e){
                $data['msg'] = $e->getMessage();
                $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
                $this->getResponse()->setHeader('Status','404 File not found');
            }catch(Exception $e){
                $data['msg'] = $e->getMessage();
                $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
                $this->getResponse()->setHeader('Status','404 File not found');
            }
            $jsonData = json_encode($data);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($jsonData);
        }
    }


    public function jauAction() {
        if($this->getRequest()->isPost() && $this->getRequest()->getPost('email')){
            $session            = Mage::getSingleton('core/session');
            $customerSession    = Mage::getSingleton('customer/session');
            $email              = (string) $this->getRequest()->getPost('email');

            $data = array(
                'msg' => 'Success! You\'ve been signed up!',
            );
            try{
                if (!Zend_Validate::is($email, 'EmailAddress')) {
                    Mage::throwException($this->__('Please enter a valid email address.'));
                }
                if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
                    !$customerSession->isLoggedIn()) {
                    Mage::throwException($this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::helper('customer')->getRegisterUrl()));
                }
                $ownerId = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email)
                    ->getId();
                if ($ownerId) {
                    Mage::getSingleton('customer/session')->addSuccess('This email address is already assigned to another user.');
                }
                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                    $data['msg'] = 'Confirmation request has been sent.';
                }else{
                    $data['msg'] = 'Thank you for your subscription.';
                }
            }catch(Mage_Core_Exception $e){
                $data['msg'] = $e->getMessage();
            }catch(Exception $e){
                $data['msg'] = $e->getMessage();
            }

            Mage::getSingleton('customer/session')->addSuccess($data['msg']);

        }
            $this->_redirect("/");
            return;

    }




}