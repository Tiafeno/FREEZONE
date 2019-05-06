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
        add_action('rest_api_init', [&$this, 'register_rest_order']);

        // Quotation
        add_action('rest_api_init', function () {
            register_rest_route('api', '/quotations/', [
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [new \apiQuotation(), 'collect_quotations'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args'                => []
                ),
            ]);
        });

        add_action('rest_api_init', function () {
            register_rest_route('api', '/suppliers/', [
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [new \apiSupplier(), 'collect_suppliers'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args'                => []
                ),
            ]);
        });

        add_action('rest_api_init', function () {

            register_rest_route('api', '/product/', [
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [new \apiProduct(), 'collect_products'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args'                => []
                ),
            ]);

        });

    }

    public function register_rest_supplier() {
        $metas = ['company_name', 'commission', 'address', 'phone', 'reference'];
        foreach ( $metas as $meta ) {
            register_rest_field('user', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, 'user_'.$object->ID);
                },
                'get_callback' => function ($object, $field_name) {
                    return get_field($field_name, 'user_'.$object['id']);
                }
            ]);
        }
    }

    public function register_rest_fz_product() {
        $metas = ['price', 'date_add', 'date_review', 'product_id', 'total_sales', 'user_id'];
        foreach ( $metas as $meta ) {
            register_rest_field('fz_product', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, (int) $object->ID);
                },
                'get_callback' => function ($object, $field_name) {
                    return get_field($field_name, (int)$object['id']);
                }
            ]);
        }
    }

    public function register_rest_order() {
        $post_types = wc_get_order_types();
        $metas = ['user_id', 'status'];
        foreach ($post_types as $type) {
            foreach ($metas as $meta) {
                register_rest_field($type, $meta, [
                    'update_callback' => function ($value, $object, $field_name) {
                        return update_field($field_name, $value, (int) $object->ID);
                    },
                    'get_callback' => function ($object, $field_name) {
                        return get_field($field_name, (int)$object['id']);
                    }
                ]);
            }

            register_rest_field($type, 'line_items', [
                'get_callback' => function ($object, $field_name) {
                    return $object;
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
        $data['wc'] = get_field('wc', 'option');
        return $data;
    }, 10, 2);

});

new fzAPI();