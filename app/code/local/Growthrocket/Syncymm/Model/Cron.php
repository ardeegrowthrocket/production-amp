<?php
class Growthrocket_Syncymm_Model_Cron{

    /**
     * Run YMM Sync
     */
	public function runSync()
    {
        Mage::log('Scheduled running..', null, 'dd_ymm_sync.log', true);
        Mage::getModel('gr_syncymm/sync')->sync();
	} 
}