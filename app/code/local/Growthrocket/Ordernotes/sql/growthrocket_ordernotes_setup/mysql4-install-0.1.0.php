<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute("order", "order_notes", array("type"=>"varchar"));
$installer->addAttribute("quote", "order_notes", array("type"=>"varchar"));
$installer->endSetup();
	 