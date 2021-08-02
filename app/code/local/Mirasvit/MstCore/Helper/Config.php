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
 * @package   mirasvit/extension_mcore
 * @version   1.0.24
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */


class Mirasvit_MstCore_Helper_Config extends Mage_Core_Helper_Data
{
    const UPDATES_FEED_URL    = 'http://mirasvit.com/blog/category/updates/feed/';
    const EXTENSIONS_FEED_URL = 'http://mirasvit.com/pc/feed/';
    const STORE_URL           = 'http://mirasvit.com/estore/';
    const DEVELOPER_IP        = 'mstcore/logger/developer_ip';
    const NOTIFICATION_STATUS = 'mstcore/notification/status';

    public function getDeveloperIp()
    {
        $ips = explode(',', Mage::getStoreConfig(self::DEVELOPER_IP));

        return $ips;
    }

    /**
     * Is Mirasvit notifications enabled
     *
     * @return int
     */
    public function isNotificationsEnabled()
    {
        return (int)Mage::getStoreConfig(self::NOTIFICATION_STATUS);
    }
}

if (!function_exists('pr')) {
    function pr($arr)
    {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }
}