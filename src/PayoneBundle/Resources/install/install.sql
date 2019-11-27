CREATE TABLE IF NOT EXISTS `bundle_payone_registry` (
  `internal_payment_id` VARCHAR(32) NOT NULL,
  `payone_reference` VARCHAR(24) COLLATE utf8_bin NOT NULL DEFAULT '',
   PRIMARY KEY (`internal_payment_id`,`payone_reference`)
  )
  ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `bundle_payone_transaction_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(32) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL,
  `method` VARCHAR(32),
  `payone_reference` VARCHAR(24) COLLATE utf8_bin NOT NULL DEFAULT '',
  `txid` VARCHAR(32),
  `data` TEXT,
   PRIMARY KEY (`id`)
  )
  ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;