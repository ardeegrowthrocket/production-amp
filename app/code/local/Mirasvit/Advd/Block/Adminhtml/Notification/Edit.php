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



class Mirasvit_Advd_Block_Adminhtml_Notification_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'notification_id';
        $this->_blockGroup = 'advd';
        $this->_controller = 'adminhtml_notification';

        $this->_removeButton('delete')
            ->_removeButton('back');

        if (Mage::registry('current_model')->getId() > 0) {
            $this->_addButton('saveandsend', array(
                'label' => Mage::helper('advd')->__('Send Test Email'),
                'onclick' => 'saveAndSend(this, false, \''.Mage::getSingleton('advd/config')->getTestEmail().'\')',
            ), 0, -100);
        }
    }

    public function getHeaderText()
    {
        return Mage::helper('advr')->__(
            "Email Notifications for user '%s'",
            $this->htmlEscape(Mage::registry('current_model')->getTitle())
        );
    }
}
