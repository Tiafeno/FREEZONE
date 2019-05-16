<?php
namespace model;

class fzModel {
    public function __construct ($instance = false) {
        if ( ! $instance )
            add_action('fz_activate_theme', function () {
                global $wpdb;
                $sav_request = <<<SAV
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
      `date_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`ID`));
SAV;
                $wpdb->query($sav_request);

                $quotation_product_sql = <<<QTP
    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quotation_product (
      `ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `order_id` BIGINT(20) UNSIGNED NOT NULL,
      `product_id` BIGINT(20) UNSIGNED NOT NULL,
      `status` TINYINT(1) NOT NULL DEFAULT 0,
      `suppliers` LONGTEXT NULL,
      PRIMARY KEY (`ID`));
QTP;
                $wpdb->query($quotation_product_sql);

            });
    }

    public static function getInstance() {
        return new self(true);
    }

    public function get_products() {
        global $wpdb;
        $sql = <<<SQL
SELECT pts.ID, pts.post_title FROM $wpdb->posts as pts WHERE pts.post_type = "product" AND pts.post_status = "publish"
SQL;
        $results = $wpdb->get_results($sql);

        return $results;

    }

}

