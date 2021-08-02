<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');

$installer->startSetup();

$installer->addAttribute('customer_address', 'destination_type', array(
    'type'               => 'varchar',
    'label'              => 'Destination Type',
    'required'           => false,
    'visible'            => false,
    'system'             => false,
    'visible'            => true,
));

$attributes = array('destination_type');
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

$installer->endSetup();
