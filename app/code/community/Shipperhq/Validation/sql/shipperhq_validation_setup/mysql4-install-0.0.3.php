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

/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');
$installer->addAttribute('customer_address', 'address_valid', array(
    'type'               => 'varchar',
    'label'              => 'Address Validated',
    'input'              => 'select',
    'source'             => 'shipperhq_shipper/source_validation_result',
    'position'          => 200,
    'default_value'     => 'none',
    'required'           => false,
    'visible'            => true,
    'system'             => false,
    'validate_rules'     => 'a:0:{}',
));

$installer->addAttribute('customer_address', 'destination_type', array(
    'type'               => 'varchar',
    'label'              => 'Destination Type',
    'required'           => false,
    'visible'            => false,
    'system'             => false,
    'visible'            => true,
    'position'          => 201,
    'validate_rules'     => 'a:0:{}',
));

$attributes = array('address_valid', 'destination_type');
$defaultUsedInForms = array(
    'adminhtml_customer_address',
    'customer_address_edit',
    'customer_register_address'
);
foreach ($attributes as $attributeCode) {
    $attribute = $eavConfig->getAttribute('customer_address', $attributeCode);
    if (!$attribute) {
        continue;
    }
    if (false === ($attribute->getData('is_system') == 1 && $attribute->getData('is_visible') == 1)) {
        $attribute->setData('used_in_forms', $defaultUsedInForms);
    }
    $attribute->save();
};

$text = Mage::helper('wsalogger')->getNewVersion() > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text';

$addressValid = array(
    'type' => $text,
    'comment' => 'Shipperhq Address Validation',
    'nullable' => 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'address_valid')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'address_valid', $addressValid);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_address'), 'address_valid')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order_address'), 'address_valid', $addressValid);
}

$installer->endSetup();


