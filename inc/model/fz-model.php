<?php

add_action('fz_activate_theme', function () {
    global $wpdb;
    $sav_request = <<<SQL
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sav (
  `ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `mark` VARCHAR(100) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `reference` VARCHAR(45) NULL,
  `product_number` VARCHAR(45) NULL COMMENT 'P/N',
  `serial_number` VARCHAR(45) NULL,
  `description` LONGTEXT NOT NULL,
  `date_appointment` DATETIME NOT NULL,
  `date_add` TIMESTAMP NOT  NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`));
SQL;
    $wpdb->query($sav_request);

});