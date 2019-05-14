<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 27/04/2019
 * Time: 23:49
 */

namespace api;

use classes\fzSupplier;

if (!defined('ABSPATH')) {
    exit;
}

class fzAPI
{
    public function __construct ()
    {
        add_action('rest_api_init', [&$this, 'register_rest_supplier']);
        add_action('rest_api_init', [&$this, 'register_rest_fz_product']);
        add_action('rest_api_init', [&$this, 'register_rest_order']);

        // Quotation
        add_action('rest_api_init', function () {
            // TODO: Ceci autorise tous les sites web d'accéder au contenue via l'API
            header("Access-Control-Allow-Origin: *");

            register_rest_route('api', '/quotations/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiQuotation(), 'collect_quotations'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ],
            ]);

            register_rest_route('api', '/suppliers/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiSupplier(), 'collect_suppliers'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ],
            ]);

            register_rest_route('api', '/product/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiProduct(), 'collect_products'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ],
            ]);

            register_rest_route('api', '/fz_product/(?P<action>\w+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiArticle(), 'action_collect_articles'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);

            register_rest_route('api', '/supplier/(?P<action>\w+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiSupplier(), 'action_collect_suppliers'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);

            register_rest_route('api', '/mail/(?P<order_id>\d+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiMail(), 'send_order_client'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => [
                        'order_id' => [
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param);
                            }
                        ]
                    ]
                ]
            ], false);

        });

    }

    public function register_rest_supplier ()
    {
        $metas = ['company_name', 'commission', 'address', 'phone', 'reference'];
        foreach ( $metas as $meta ) {
            register_rest_field('user', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, 'user_' . $object->ID);
                },
                'get_callback' => function ($object, $field_name) {
                    return get_field($field_name, 'user_' . $object['id']);
                }
            ]);
        }
    }

    public function register_rest_fz_product ()
    {
        $metas = ['price', 'date_add', 'date_review', 'product_id', 'total_sales', 'user_id'];
        foreach ( $metas as $meta ) {
            register_rest_field('fz_product', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, (int)$object->ID);
                },
                'get_callback' => function ($object, $field_name)  {
                    $value = get_field($field_name, (int)$object['id']);
                    return $value;
                }
            ]);
        }

        $params = $_REQUEST;
        if (isset($params['context']) && $params['context'] === "edit") {
            register_rest_field('fz_product', 'supplier', [

                'get_callback' => function ($object)  {
                    $user_id = get_field('user_id', (int)$object['id']);
                    return new fzSupplier((int) $user_id);
                }
            ]);
        }

    }

    public function register_rest_order ()
    {
        $post_types = wc_get_order_types();
        $metas = ['user_id', 'position'];
        foreach ( $post_types as $type ) {
            foreach ( $metas as $meta ) {
                register_rest_field($type, $meta, [
                    'update_callback' => function ($value, $object, $field_name) {
                        return update_field($field_name, $value, (int)$object->ID);
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
add_action('rest_api_init', function () {

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