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

    public static function getInstance() {
        return new self(true);
    }

    /**
     * @param $order_id
     * @return null|string
     */
    public function quotation_exist( $order_id ) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}quotation WHERE order_id = %d";
        $result = $wpdb->get_var($wpdb->prepare($sql, intval($order_id)));

        return $result;
    }

    /**
     * @param $user_id
     * @return null|string
     */
    public function has_user_quotation( $user_id ) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}quotation WHERE user_id = %d";
        $result = $wpdb->get_var($wpdb->prepare($sql, intval($user_id)));

        return $result;
    }

    /**
     * @param $user_id
     * @return array|null|object
     */
    public function get_user_quotations( $user_id ) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation WHERE user_id = %d";
        $result = $wpdb->get_results($wpdb->prepare($sql, intval($user_id)));

        return $result;
    }

    /**
     * @param $order_id
     * @return null|object
     */
    public function get_quotation( $order_id ) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation WHERE order_id = %d";
        $result = $wpdb->get_row($wpdb->prepare($sql, intval($order_id)));

        return $result;
    }

    /**
     * @param int $order_id
     * @param int $user_id
     * @return false|int
     */
    public function set_quotation( $order_id, $user_id = 0 ) {
        global $wpdb;
        $User = wp_get_current_user();
        $user_id = $user_id === 0 ? $User->ID : $user_id;
        $data   = [
            'order_id' => intval($order_id),
            'user_id'  => $user_id,
            'status'   => 0,
        ];
        $format = [ '%d', '%d', '%d' ];
        $result = $wpdb->insert( $wpdb->prefix.'quotation', $data, $format );

        return $result;
    }

    public function remove_quotation( $order_id ) {
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix.'quotation', ['order_id' => intval($order_id)], ['%d']);

        return $result;
    }

    public function update_quotation_status( $order_id, $status = 0 ) {
        global $wpdb;
        if (!is_numeric($order_id)) return false;
        $result = $wpdb->update($wpdb->prefix.'quotation',
            ['status' => intval($status)],
            ['order_id' => $order_id],
            ['%d'],
            ['%d']);

        return $result;
    }

    /**
     * @param $order_id
     * @return false|int
     */
    public function get_quotation_products( $order_id) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation_product WHERE order_id = %d";
        $result = $wpdb->query($wpdb->prepare($sql, $order_id));

        return $result;
    }

    public function remove_quotation_pts( $order_id ) {
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix.'quotation_product', ['order_id' => intval($order_id)], ['%d']);

        return $result;
    }

    /**
     * @param int $order_id
     * @param int $product_id
     * @param int $status
     * @return false|int
     */
    public function set_product_qt( $order_id, $product_id, $status = 0 ) {
        global $wpdb;
        if (!is_numeric($order_id) || !is_numeric($product_id)) return false;
        $data   = [
            'order_id'    => intval($order_id),
            'product_id'  => intval($product_id),
            'status'      => $status,
        ];
        $format = [ '%d', '%d', '%d' ];
        $result = $wpdb->insert( $wpdb->prefix.'quotation_product', $data, $format );
        $wpdb->flush();

        return $result;
    }

    /**
     * @param int $order_id
     * @param int $product_id
     * @return array|null|object|false|void
     */
    public function get_product_qt( $order_id, $product_id ) {
        global $wpdb;
        if (!is_numeric($order_id) || !is_numeric($product_id)) return false;
        $sql = "SELECT * FROM {$wpdb->prefix}quotation_product WHERE order_id = %d AND product_id = %d";
        $result = $wpdb->get_row($wpdb->prepare($sql, intval($order_id), intval($product_id)));

        return $result;
    }

    /**
     * @param int $order_id
     * @param int $product_id
     * @param int $status
     * @return bool|false|int
     */
    public function update_product_qt_status( $order_id, $product_id, $status = 0 ) {
        global $wpdb;
        if (!is_numeric($order_id) || !is_numeric($product_id)) return false;
        $result = $wpdb->update($wpdb->prefix.'quotation_product',
            ['status' => intval($status)],
            ['order_id' => $order_id, 'product_id' => $product_id],
            ['%d'],
            ['%d', '%d']);

        return $result;
    }

    /**
     * @param int $order_id
     * @param int $product_id
     * @param array $suppliers
     * @return bool|false|int
     */
    public function update_product_qt_suppliers( $order_id, $product_id, $suppliers) {
        global $wpdb;
        if ( ! is_numeric($order_id) || ! is_numeric($product_id) ) return false;
        if ( ! is_array($suppliers) ) return false;
        $result = $wpdb->update($wpdb->prefix.'quotation_product',
            ['suppliers' => serialize($suppliers)],
            ['order_id' => $order_id, 'product_id' => $product_id],
            ['%s'],
            ['%d', '%d']);

        return $result;

    }

}

