<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 27/04/2019
 * Time: 23:49
 */

namespace api;

use classes\fzSupplierArticle;
use classes\fzQuotation;
use classes\fzQuotationProduct;
use classes\fzSupplier;

if (!defined('ABSPATH')) {
    exit;
}

class fzAPI
{
    public function __construct () {
        add_action('rest_api_init', [&$this, 'register_rest_supplier']);
        add_action('rest_api_init', [&$this, 'register_rest_fz_product']);
    }

    public function register_rest_supplier() {
        $metas = ['company_name', 'commission', 'address', 'phone'];
        foreach ( $metas as $meta ) {
            register_rest_field('user', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, (int) $object->ID);
                },
                'get_callback' => function ($object, $field_name) {
                    if ( ! is_user_logged_in() ) return 'Not allow';
                    return get_field($field_name, (int)$object['id']);
                }
            ]);
        }
    }

    public function register_rest_fz_product() {
        $metas = ['statut', 'price', 'date_add', 'date_review', 'product_id', 'total_sales', 'user_id'];
        foreach ( $metas as $meta ) {
            register_rest_field('fz_product', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, (int) $object->ID);
                },
                'get_callback' => function ($object, $field_name) {
                    if ( ! is_user_logged_in() ) return 'Not allow';
                    return get_field($field_name, (int)$object['id']);
                }
            ]);
        }
    }

}

/**
 * WP_REST_Server::READABLE = ‘GET’
 * WP_REST_Server::EDITABLE = ‘POST, PUT, PATCH’
 * WP_REST_Server::CREATABLE = ‘POST’
 * WP_REST_Server::DELETABLE = ‘DELETE’
 * WP_REST_Server::ALLMETHODS = ‘GET, POST, PUT, PATCH, DELETE’
 */
add_action('rest_api_init', function() {

    // Ajouter des informations utilisateur dans la reponse
    add_filter('jwt_auth_token_before_dispatch', function ($data, $user) {
        // Tells wordpress the user is authenticated
        wp_set_current_user($user->ID);
        $user_data = get_userdata($user->ID);
        $data['data'] = $user_data;
        return $data;
    }, 10, 2);
});

new fzAPI();