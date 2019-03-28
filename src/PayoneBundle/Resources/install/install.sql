CREATE TABLE IF NOT EXISTS `bundle_payone_registry` (
  `internal_payment_id` VARCHAR(32) NOT NULL,
  `payone_reference` VARCHAR(24) COLLATE utf8_bin NOT NULL DEFAULT '',
   PRIMARY KEY (`internal_payment_id`,`payone_reference`)
  )
  ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;