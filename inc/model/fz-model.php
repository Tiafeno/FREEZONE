<?php
namespace model;

class fzModel {
    public function __construct () {
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

            $quotation_sql = <<<QT
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quotation (
  `ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `status` TINYINT(1) NULL,
  `date_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`));
QT;
            $wpdb->query($quotation_sql);
        });
    }

    public function get_quotation_product( $order_id) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation_product WHERE order_id = %d";
        $result = $wpdb->query($wpdb->prepare($sql, $order_id));

        return $result;
    }

    public function quotation_exist( $order_id ) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}quotation WHERE order_id = %d";
        $result = $wpdb->get_var($wpdb->prepare($sql, intval($order_id)));

        return $result;
    }

    public function has_user_quotation( $user_id ) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}quotation WHERE user_id = %d";
        $result = $wpdb->get_var($wpdb->prepare($sql, intval($user_id)));

        return $result;
    }

    public function get_user_quotations( $user_id ) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation WHERE user_id = %d";
        $result = $wpdb->get_results($wpdb->prepare($sql, intval($user_id)));

        return $result;
    }

    public function get_quotation( $order_id ) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation WHERE order_id = %d";
        $result = $wpdb->get_results($wpdb->prepare($sql, intval($order_id)));

        return $result;
    }

    public function set_quotation( $order_id, $user_id = 0 ) {
        global $wpdb;
        $User = wp_get_current_user();
        $user_id = $user_id === 0 ? $User->ID : $user_id;
        $data   = [
            'order_id' => intval($order_id),
            'user_id'  => $user_id,
            'status'   => 0,
        ];
        $format = [ '%d', '%d' ];
        $result = $wpdb->insert( $wpdb->prefix.'quotation', $data, $format );

        return $result;
    }

}

