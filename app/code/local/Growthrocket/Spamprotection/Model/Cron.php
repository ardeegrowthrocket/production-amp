<?php
class Growthrocket_Spamprotection_Model_Cron{

    /**
     * Clean table
     */
	public function clearSpamLogTable()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('spamprotection/spamprotection');
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $connection->query("TRUNCATE TABLE `{$table}`");
    }
}