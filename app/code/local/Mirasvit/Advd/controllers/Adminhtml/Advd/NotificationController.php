<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/extension_advr
 * @version   1.2.13
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



class Mirasvit_Advd_Adminhtml_Advd_NotificationController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('dashboard');

        return $this;
    }

    public function editAction()
    {
        $this->initModel();

        $this->_initAction();

        $this->_title('Email Notifications');

        $this->_addContent($this->getLayout()->createBlock('advd/adminhtml_notification_edit'));

        $this->renderLayout();
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getParams()) {
            $model = $this->initModel();

            if ($this->getRequest()->getParam('back') == 'send') {
                $model->setIsTest(true);
                $model->setRecipientEmail($this->getRequest()->getParam('test_email'));
                $model->send();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('advd')->__('Test email was successfully sent'));

                $this->_redirect('*/*/edit', array('id' => $model->getId()));

                return;
            }

            try {
                $model->addData($data);
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess('Email Nofiticaiton was successfully saved');

                $this->_redirect('*/advd_dashboard/global');

                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));

                return;
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(__('Unable to find item to save'));
            $this->_redirect('*/advd_dashboard/global');
        }
    }

    protected function initModel()
    {
        $model = Mage::getModel('advd/notification');

        if ($this->getRequest()->getParam('id')) {
            $model->load($this->getRequest()->getParam('id'));
        }

        Mage::register('current_model', $model);

        return $model;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('dashboard');
    }
}
