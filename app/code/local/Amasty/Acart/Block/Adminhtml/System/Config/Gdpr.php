<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */
class  Amasty_Acart_Block_Adminhtml_System_Config_Gdpr extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $geoIpModel = Mage::getModel('amgeoip/import');
        if (!$geoIpModel->isDone()) {
            $element->setDisabled('disabled');
            $element->setComment($this->getComment());
        }

        return parent::_getElementHtml($element);
    }

    /**
     * @return string
     */
    private function getComment()
    {
        $link = $this->getUrl('adminhtml/system_config/edit/section/amgeoip');
        $linkText = Mage::helper('amacart')->__('import GEOIP Data');
        $linkTag = sprintf('<a href="%s">%s</a>', $link, $linkText);
        $comment = Mage::helper('amacart')->__('For use this setting you need %s', $linkTag);

        return $comment;
    }
}
