<?php

/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */

$installer = $this;

$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

if($version >= 8 ) {
    $splitShippedStatus = array(
        'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
        'length'	=> 50,
        'comment' 	=> 'Shipperhq Shipped Status',
        'nullable' 	=> 'true');
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/shipment_grid'), 'split_shipped_status')){
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment_grid'),'split_shipped_status',$splitShippedStatus);
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/shipment'), 'split_shipped_status')){
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment'),'split_shipped_status',$splitShippedStatus);
    }
    if  (Mage::helper('wsalogger')->isEnterpriseEdition() && $installer->tableExists('enterprise_sales_shipment_grid_archive')) {
        if(!$installer->getConnection()->tableColumnExists($installer->getTable('enterprise_sales_shipment_grid_archive'), 'split_shipped_status')){
            $installer->getConnection()->addColumn($installer->getTable('enterprise_sales_shipment_grid_archive'),'split_shipped_status',$splitShippedStatus);
        }
    }

}

$installer->addAttribute('shipment', 'split_shipped_status', array(
    'type'=>'varchar',
    'input' => 'text',
));

$installer->endSetup();