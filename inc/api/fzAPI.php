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
        add_action('rest_api_init', [&$this, 'register_rest_user']);
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

            register_rest_route('api', '/sav/', [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [new \apiSav(), 'get'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ]
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

            /**
             * Pour récuperer les fournisseurs
             */
            register_rest_route('api', '/supplier/(?P<action>\w+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [new \apiSupplier(), 'action_collect_suppliers'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);


            /**
             * Pour récuperer les clients
             */
            register_rest_route('api', '/import/csv/articles', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        $Import = new \apiImport();
                        $Import->import_article_csv();
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);

            /**
             * Pour récuperer les clients
             */
            register_rest_route('api', '/clients/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
                        $start = isset($_REQUEST['length']) ? (int)$_REQUEST['start'] : 0;
                        $args = [
                            'number'  => $length,
                            'offset'  => $start,
                            'orderby' => 'registered',
                            'role__in' => ['fz-particular'],
                            'order'    => 'DESC'
                        ];
                        if ( ! empty($_REQUEST['responsible']) ) {
                            $args = array_merge($args, [
                                'meta_query' => [
                                    [
                                        'key'   => 'responsible',
                                        'value' => (int)$_REQUEST['responsible']
                                    ]
                                ]
                            ]);
                        }

                        
                        $user_query = new \WP_User_Query($args);
                        if (!empty($user_query->get_results())) {
                            $results = [];
                            $request = new \WP_REST_Request();
                            $request->set_param('context', 'edit');

                            foreach ( $user_query->get_results() as $user ) {
                                $user_controller = new \WC_REST_Customers_V2_Controller();
                                $response = $user_controller->prepare_item_for_response(new \WC_Customer($user->ID), $request);

                                // TODO: Ne pas afficher au commercial certain contenue du client
                                $results[] = $response->data;
                            }
                            return [
                                "recordsTotal"    => $user_query->total_users,
                                "recordsFiltered" => $user_query->total_users,
                                'data' => $results
                            ];
                        } else {
                            return [
                                "recordsTotal"    => 0,
                                "recordsFiltered" => 0,
                                'data' => []
                            ];
                        }
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);


            /**
             * Envoyer un mail au client pour le devis
             */
            register_rest_route('api', '/mail/order/(?P<order_id>\d+)', [
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

            /**
             * Envoyer un mail pour un fournisseur.
             * Cette registre permet d'envoyer un mail avec un lien pour mettre à jours l'articles
             * en attente du fournisseur.
             */
            register_rest_route('api', '/mail/review/(?P<supplier_id>\d+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        $supplier_id = intval($rq['supplier_id']);
                        $subject = stripslashes($_REQUEST['subject']);
                        $content = stripslashes($_REQUEST['message']);
                        $articles = stripslashes($_REQUEST['articles']);
                        $cc_field = isset($_REQUEST['cc']) ? trim($_REQUEST['cc']) : null;
                        $cc_field = is_null($cc_field) ? null : \explode(',', stripslashes($cc_field));
                        $cc = '';
                        if (is_array($cc_field)) {
                            foreach ( $cc_field as $field_name ) {
                                $field_value = get_field($field_name, 'user_' . $supplier_id);
                                $cc .= $field_value ? $field_value : '';
                            }
                        }

                        do_action('fz_submit_articles_for_validation', $supplier_id, $subject, $content, $cc, $articles);

                    }
                ]
            ]);

        });

    }

    public function register_rest_user ()
    {
        $metas = [
            'company_name',
            'address',
            'mail_commercial_cc',
            'mail_logistics_cc',
            'phone',
            'reference',
            'role_office', // 1: Acheteur, 2: Revendeur & 0: En attente
            'client_status', // Particular or Company
            'stat',
            'nif',
            'rc',
            'cif'
        ];
        $User = wp_get_current_user();
        $admin = in_array('administrator', $User->roles) ? 'administrator' : false;
        foreach ( $metas as $meta ) {
            register_rest_field('user', $meta, [
                'update_callback' => function ($value, $object, $field_name) use ($admin) {
                    if ($admin !== 'administrator' && $field_name === 'company_name') {
                        return true;
                    } else return update_field($field_name, $value, 'user_' . $object->ID);

                },
                'get_callback' => function ($object, $field_name) use ($admin) {
                    if ($admin !== 'administrator' && $field_name === 'company_name') {
                        return get_field('reference', 'user_' . $object['id']);
                    } else return get_field($field_name, 'user_' . $object['id']);


                }
            ]);
        }

        // Ce champ est pour les clients qui contient les commercials responsable
        register_rest_field('user', 'responsible', [
            'update_callback' => function ($value, $object, $field_name) {
                return update_user_meta($object->ID, $field_name, $value);
            },
            'get_callback' => function ($object, $field_name) {
                $responsible = get_user_meta($object['id'], $field_name, true);
                return $responsible;
            }
        ]);

    }

    public function register_rest_fz_product ()
    {
        $metas = ['price', 'price_dealer', 'date_add', 'date_review', 'product_id', 'total_sales', 'user_id'];
        foreach ( $metas as $meta ) {
            register_rest_field('fz_product', $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_field($field_name, $value, (int)$object->ID);
                },
                'get_callback' => function ($object, $field_name) {
                    $value = get_field($field_name, (int)$object['id']);
                    return $value;
                }
            ]);
        }

        register_rest_field('fz_product', 'marge', [
            'update_callback' => function ($value, $object, $field_name) {
                $product_id = get_field('product_id', (int)$object->ID);
                $product = new \WC_Product((int)$product_id);
                return $product->update_meta_data("_fz_marge", $value);
            },
            'get_callback' => function ($object, $field_name) {
                $product_id = get_field('product_id', (int)$object['id']);
                $product = new \WC_Product((int)$product_id);
                $marge = $product->get_meta("_fz_marge");

                return $marge;
            }
        ]);

        register_rest_field('fz_product', 'product_status', [
            'update_callback' => function ($value, $object, $field_name) {
                $product_id = get_field('product_id', (int)$object->ID);
                $product = new \WC_Product((int)$product_id);
                return $product->set_status($value);
            },
            'get_callback' => function ($object, $field_name) {
                $product_id = get_field('product_id', (int)$object['id']);
                $product = new \WC_Product((int)$product_id);
                $marge = $product->get_status();

                return $marge;
            }
        ]);

        register_rest_field('fz_product', 'product_thumbnail', [
            'get_callback' => function ($object, $field_name) {
                $product_id = get_field('product_id', (int)$object['id']);
                $product_controller = new \WC_REST_Products_V2_Controller();
                $request = new \WP_REST_Request();
                $request->set_param('context', 'edit');
                $product_response = $product_controller->prepare_object_for_response(new \WC_Product((int)$product_id), $request);
                $images = $product_response->data['images'];
                return is_array($images) && !empty($images) ? $images[0] : ['src' => 0, 'src' => ''];
            }
        ]);

        register_rest_field('fz_product', 'marge_dealer', [
            'update_callback' => function ($value, $object, $field_name) {
                $product_id = get_field('product_id', (int)$object->ID);
                $product = new \WC_Product((int)$product_id);
                return $product->update_meta_data("_fz_marge_dealer", $value);
            },
            'get_callback' => function ($object, $field_name) {
                $product_id = get_field('product_id', (int)$object['id']);
                $product = new \WC_Product((int)$product_id);
                $marge = $product->get_meta("_fz_marge_dealer");

                return $marge;
            }
        ]);

        $params = $_REQUEST;
        if (isset($params['context']) && $params['context'] === "edit") {
            register_rest_field('fz_product', 'supplier', [
                'get_callback' => function ($object) {
                    $user_id = get_field('user_id', (int)$object['id']);
                    return new fzSupplier((int)$user_id);
                }
            ]);
            register_rest_field('fz_product', 'supplier', [
                'get_callback' => function ($object) {
                    $user_id = get_field('user_id', (int)$object['id']);
                    return new fzSupplier((int)$user_id);
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
