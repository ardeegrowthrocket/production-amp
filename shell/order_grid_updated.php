<?php

require_once 'abstract.php';

class Shell_Order_Grid_Update extends Mage_Shell_Abstract
{
    public function run()
    {
        try {
            Mage::getModel('sales/order')->getResource()->updateGridRecords(
                Mage::getResourceModel('sales/order_collection')->getAllIds()
            );
            echo 'Done!' . PHP_EOL;
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

$shell = new Shell_Order_Grid_Update();
$shell->run();