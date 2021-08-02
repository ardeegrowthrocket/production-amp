<?php

$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE gr_site_redirection ADD website_id int(3);
UPDATE gr_site_redirection set website_id = 7;
");
$installer->endSetup();