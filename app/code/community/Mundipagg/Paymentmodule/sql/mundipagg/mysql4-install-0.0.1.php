<?php
$installer = $this;
$test = $this->getTable('sales/order');
$installer->startSetup();
$installer->run(
    "
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` 
ADD `custom_field_one` VARCHAR( 255 ) NOT NULL,
ADD `custom_field_two` VARCHAR( 255 ) NOT NULL;
  
ALTER TABLE `{$installer->getTable('sales/order_payment')}` 
ADD `custom_field_one` VARCHAR( 255 ) NOT NULL,
ADD `custom_field_two` VARCHAR( 255 ) NOT NULL;
"
);
$installer->run("
ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `mundipagg_interest` DECIMAL( 10, 2 ) NOT NULL;
");
$installer->endSetup();