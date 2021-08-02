<?php
require_once "Mage/Newsletter/controllers/SubscriberController.php";  
class Growthrocket_Newsletter_Newsletter_SubscriberController extends Mage_Newsletter_SubscriberController{

    public function postDispatch()
    {
        parent::postDispatch();
        Mage::dispatchEvent('controller_action_postdispatch_adminhtml', array('controller_action' => $this));
    }

    /**
     * Rewrite newsletter
     */
    public function newAction()
    {

        $isAjax = (bool) $this->getRequest()->getPost('is_ajax');
        $result = array(
            'message' =>'',
            'class'  => ''
        );





        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
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










            try {

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
                if ($ownerId !== null && $ownerId != $customerSession->getId()) {
                    Mage::throwException($this->__('This email address is already assigned to another user.'));
                }
                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {

                    $message = $this->__('Confirmation request has been sent.');
                    if($isAjax) {
                        $result['message'] = 'Subscribed! Check your inbox to confirm.';
                        $result['class'] = 'success';
                    }else {
                        $session->addSuccess($message);
                    }

                }
                else {
                    $message = $this->__('Confirmation request has been sent.');
                    if($isAjax) {
                        $result['message'] = 'Subscribed! Check your inbox to confirm.';
                        $result['class'] = 'success';
                    }else {
                        $session->addSuccess($message);
                    }
                }
            }
            catch (Mage_Core_Exception $e) {

                if($isAjax) {
                    $result['message'] = $this->__($e->getMessage());
                    $result['class'] = 'error';
                }else {
                    $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
                }

            }
            catch (Exception $e) {

                if($isAjax) {
                    $result['message'] = $this->__('There was a problem with the subscription.');
                    $result['class'] = 'error';
                }else {
                    $session->addException($e, $this->__('There was a problem with the subscription.'));
                }
            }
        }

        if($isAjax){
            $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }else {
            $this->_redirectReferer();
        }

    }
}
				