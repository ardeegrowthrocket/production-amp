<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$table = $installer->getTable('core/config_data');
$select = $connection->select()
    ->from($table, array('config_id', 'value'))
    ->where('path = ?', 'carriers/shipper/url');

$data = $connection->fetchAll($select);

if ($data) {
    try {
        $connection->beginTransaction();

        foreach ($data as $value) {
            if(array_key_exists('value', $value) && strpos($value['value'], 'sandbox.shipperhq.com') !== false) {
                $bind = array(
                    'path'  => 'carriers/shipper/url',
                    'value' => ""
                );
                $where = 'config_id = ' . $value['config_id'];
                $connection->update($table, $bind, $where);
            }
        }

        $connection->commit();
    } catch (Exception $e) {
        $installer->getConnection()->rollback();
        throw $e;
    }
}

$installer->endSetup();
