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



class Mirasvit_Advr_Model_Observer extends Varien_Object
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onControllerActionPredispatch()
    {
        try {
            $menu = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu');
            $acl = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('acl');

            if (Mage::getSingleton('advr/config')->isLinkUnderReport()) {
                $itemAdvr = null;
                $itemReport = null;
                foreach ($menu->children() as $key => $children) {
                    if ($key == 'advr') {
                        $itemAdvr = $children;
                    }

                    if ($key == 'report') {
                        $itemReport = $children;
                    }
                }

                if ($itemAdvr && $itemReport) {
                    $itemReport->children()->appendChild($itemAdvr);
                }

                $menu->setNode('advr', null);
                unset($menu->advr);

                $itemAdvr = null;
                $itemReport = null;

                foreach ($acl->resources->admin->children->children() as $key => $children) {
                    if ($key == 'advr') {
                        $itemAdvr = $children;
                    }

                    if ($key == 'report') {
                        $itemReport = $children;
                    }
                }
                if ($itemAdvr && $itemReport) {
                    $itemReport->children()->appendChild($itemAdvr);
                }
                $acl->resources->admin->children->setNode('advr', null);
            }
        } catch (Exception $e) {
        }
    }

    public function geoCopyUnknown()
    {
        Mage::getSingleton('advr/postcode')->copyUnknown();
    }

    public function geoUpdate()
    {
        Mage::getSingleton('advr/postcode')->batchUpdate();
        Mage::getSingleton('advr/postcode')->batchUpdateByAddress();
        Mage::getSingleton('advr/postcode')->batchMerge();
    }
}
