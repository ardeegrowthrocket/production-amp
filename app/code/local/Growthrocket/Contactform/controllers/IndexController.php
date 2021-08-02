<?php
class Growthrocket_Contactform_IndexController extends Mage_Core_Controller_Front_Action{

    const XML_PATH_EMAIL_RECIPIENT  = 'contacts/callback/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'contacts/email/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'contacts/callback/email_template';
    const XML_PATH_ENABLED          = 'contacts/contacts/enabled';

    public function preDispatch()
    {
        parent::preDispatch();

        if( !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED) ) {
            $this->norouteAction();
        }
    }

    public function callbackAction()
    {

        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;

                $newletterdata = Mage::getModel('newslettertracker/capture');
                $newletterdata->setReferrer($_SERVER['HTTP_REFERER']);
                $newletterdata->setCurrent(Mage::helper('core/url')->getCurrentUrl());
                $newletterdata->setEmail($post['email']);
                $newletterdata->setIp(Mage::helper('core/http')->getRemoteAddr());

                $valid = 1;

                $note = '';

                $newletterdata->setCurrent(Mage::helper('core/url')->getCurrentUrl()." -- ".$note);
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

                if($valid==0){
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['phone']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['inquiry_type']), 'NotEmpty')) {
                    $error = true;
                }

                $helperCaptcha = Mage::helper('growthrocket_gtm/captcha');
                if($helperCaptcha->isEnableCaptchaCategory('contact_us')) {
                    $token = $this->getRequest()->getPost('token');
                    $response =  Mage::helper('core')->jsonDecode($helperCaptcha->verifyCaptcha($token));

                    if(!$response['success'] || $response['score'] < $helperCaptcha->getScoreThreshold()) {
                        Mage::throwException($this->__('Invalid Google Captcha request.'));
                    }
                }

                if ($error) {
                    throw new Exception();
                }

                $mailTemplate = Mage::getModel('core/email_template')
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('contact-us');

                return;
            } catch (Exception $e) {

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('contact-us');
                return;
            }

        } else {
            $this->_redirect('contact-us');
        }
    }
}