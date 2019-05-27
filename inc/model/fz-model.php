<?php
namespace model;

class fzModel {
    public function __construct ($instance = false) {
        if ( ! $instance )
            add_action('fz_activate_theme', function () {

            });
    }

    public static function getInstance() {
        return new self(true);
    }

    /**
     * Cette fonction permet de recuperer tous les produits sans limit de nombre
     * @return array|null|object
     */
    public function get_products() {
        global $wpdb;
        $sql = <<<SQL
SELECT pts.ID, pts.post_title FROM $wpdb->posts as pts 
  WHERE pts.post_type = "product" 
    AND pts.post_status = "publish"
SQL;
        $results = $wpdb->get_results($sql);

        return $results;

    }
    public function set_sav($args = []) {
        global $wpdb;
        if (!is_user_logged_in()) return false;
        $User = wp_get_current_user();
        $date_appointment = \DateTime::createFromFormat('d/m/Y', $args['date_appointment']);
        $date_appointment->setTime(0, 0, 0);
        $request = $wpdb->insert("{$wpdb->prefix}sav", [
            'user_id' => $User->ID,
            'mark' => sanitize_text_field($args['mark']),
            'type' => sanitize_text_field($args['type']),
            'reference' => sanitize_text_field($args['reference']),
            'product_number' => sanitize_text_field($args['product_number']),
            'serial_number' => sanitize_text_field($args['serial_number']),
            'description' => stripslashes($args['description']),
            'date_appointment' => $date_appointment->format('Y-m-d H:i:s')
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        return $request;
    }

    public function get_sav($sav_id) {


    }
}

