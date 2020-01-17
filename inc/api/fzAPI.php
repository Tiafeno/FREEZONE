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
        add_action('rest_api_init', [&$this, 'register_rest_faq_client']);
        
        add_action('rest_api_init', function () {
            // https://wordpress.stackexchange.com/questions/271877/how-to-do-a-meta-query-using-rest-api-in-wordpress-4-7
            add_filter('rest_catalog_query', function($args, $request) {
                $args += array(
                    'meta_key'   => $request['meta_key'],
                    'meta_value' => $request['meta_value'],
                    'meta_compare' => $request['meta_compare'],
                    'meta_query' => $request['meta_query'],
                );
                return $args;
            }, 99, 2);
        });

        // Quotation
        add_action('rest_api_init', function () {
            // Ceci autorise tous les sites web d'accéder au contenue via l'API
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

            /**
             * Permet de crée une article depuis la B.O
             */
            register_rest_route('api', '/create/article', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $request) {
                        global $wpdb;
                        $product = null;
                        extract($_REQUEST, EXTR_PREFIX_SAME, 'REST');

                        /** @var string $name */
                        /** @var string $price */
                        /** @var string $total_sales */
                        /** @var string $user_id */
                        /** @var string $product_cat */
                        /** @var string $mark */
                        /** @var string $marge */
                        /** @var string $marge_dealer */
                        /** @var string $marge_particular */
                        /** @var string $garentee */
                        $garentee = isset($garentee) ? $garentee : null;
                        $post_title = strtolower($name);
                        $request_product_exist_sql = "SELECT * FROM $wpdb->posts WHERE CONVERT(LOWER(`post_title`) USING utf8mb4) = '{$post_title}' AND post_type = 'product'";
                        $result = $wpdb->get_row($request_product_exist_sql, OBJECT);

                        $p_cat = [];
                        if ( ! is_array($product_cat)) {
                            $p_cat = explode(',', $product_cat);
                        }
                        $p_cat = array_filter($p_cat, function ($cat) { return !empty($cat); });
                        if (!is_null($result)) {
                            $product = new \WC_Product($result->ID);
                        } else {
                            $taxonomy = 'pa_brands';
                            $attr_id = wc_attribute_taxonomy_id_by_name('brands'); // @return int
                            //$attribut = wc_get_attribute($attr_id); // name, slug, id etc...

                            //$terms = get_terms(['taxonomy' => $attribut->slug, 'hide_empty' => false]);
                            //$current_term = array_filter($terms, function ($term) use ($mark) { return $term->name === $mark ;});
                            //$current_term = is_array($current_term) && !empty($current_term) ? $current_term[0] : '';

                            $term_id = get_term_by( 'name', $mark, $taxonomy )->term_id;
                            $categories = array_map(function ($cat) { return ['id' => intval($cat)]; }, $p_cat);
                            $rest_request = new \WP_REST_Request();
                            $rest_request->set_query_params([
                                'type'   => 'simple',
                                'status' => 'pending',
                                'name'   => $name,
                                'regular_price' => '0',
                                'description'   => ' ',
                                'short_description' => ' ',
                                'attributes' => [
                                    [
                                        'id' => $attr_id,
                                        'position'  => 0,
                                        'visible'   => true,
                                        'variation' => false, // for variative products in case you would like to use it for variations
                                        'options'   => array($mark) // if the attribute term doesn't exist, it will be created
                                    ]
                                ],
                                'meta_data' => [
                                    ['key'  => '_fz_marge',        'value' => intval($marge)],
                                    ['key'  => '_fz_marge_dealer', 'value' => intval($marge_dealer)],
                                    ['key'  => '_fz_marge_particular', 'value' => intval($marge_particular)]
                                ],
                                'categories' => $categories,
                                'images'     => []
                            ]);
                            $product_controller = new \WC_REST_Products_V2_Controller();
                            $response = $product_controller->create_item($rest_request);
                            if ($response instanceof \WP_REST_Response) {
                                $data = $response->get_data();
                                $product = new \WC_Product((int) $data['id']);
                            }
                        }

                        if (is_null($product)) {
                            return new \WP_REST_Response(['data' => "Produit introuvable"], 200);
                        }

                        $product->set_sku('PRD' . $product->get_id());
                        $product->save();

                        $date_now = date_i18n('Y-m-d H:i:s');
                        $rest_request = new \WP_REST_Request();
                        $rest_request->set_query_params([
                            'title'   => $name,
                            'content' => '',
                            'status' => 'pending',
                            'price'  => intval($price),
                            'total_sales' => $total_sales,
                            'user_id'     => $user_id,
                            'product_id'  => $product->get_id(),
                            'garentee'    => $garentee,
                            'date_add'    => $date_now,
                            'date_review' => $date_now

                        ]);
                        $post_controller = new \WP_REST_Posts_Controller('fz_product');
                        $response = $post_controller->create_item($rest_request);
                        $terms = array_filter($p_cat, function ($cat) { return intval($cat); });
                        if ($response instanceof \WP_REST_Response) {
                            $data = $response->get_data();
                            wp_set_post_terms( (int) $data['id'], $terms, 'product_cat' );
                        }
                        return $response;
                    },
                    'permission_callback' => function () {
                        return current_user_can('delete_posts');
                    },
                ]
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

            register_rest_route('api', '/suppliers_waiting', [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [new \apiSupplier(), 'get_accepted_item_suppliers'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);

            register_rest_route('api', '/product/(?P<action>\w+)/(?P<product_id>\d+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function(\WP_REST_Request $request) {
                        global $wpdb;
                        $action = $request['action'];
                        $product_id = (int) $request['product_id'];
                        if (is_nan($product_id)) wp_send_json_error( "L'identifiant du produit indefinie" );
                        // Récuperer tous les articles des fournisseurs qui posséde le produit
                        $sql = "SELECT SQL_CALC_FOUND_ROWS pts.ID FROM $wpdb->posts as pts
                            WHERE pts.post_type = 'fz_product'
                                AND pts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'product_id' AND CAST(meta_value AS UNSIGNED) = $product_id)";
                        $posts = $wpdb->get_results($sql);

                        // Mettre a jour tous les titres des articles de type "fz_product"
                        if ($action === 'update'):
                            $title = stripslashes($_REQUEST['title']);
                            $description = stripslashes($_REQUEST['description']);
                            foreach ($posts as $post) {
                                $response = wp_update_post([
                                    'ID'           => (int) $post->ID,
                                    'post_title'   => $title,
                                    'post_content' => $description,
                                ], true);

                                if (is_wp_error( $response )) wp_send_json_error( "{$response->get_error_message()}" );
                            }
                            wp_send_json_success( "Mise à jours effectuer avec succès" );
                        endif;

                        // Si le produit WC on supprime aussi tous les article de type "fz_product" en liaison avec celui-ci
                        if ($action === 'remove') :
                            foreach ($posts as $post) {
                                // @return (WP_Post|false|null) Post data on success, false or null on failure.
                                $response = wp_delete_post((int) $post->ID, true);
                                if (is_null( $response ) || !$response) wp_send_json_error("Une erreur s'est produit pendant la suppression de l'article");
                            }

                            wp_send_json_success( "Action successfully complete" );
                        endif;

                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('delete_posts');
                    }
                ]
            ]);

            /**
             * Pour récuperer les clients
             */
            register_rest_route('api', '/import/csv', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        $Import = new \apiImport();
                        $Import->import_article_csv();
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('delete_posts');
                    }
                ],
            ]);

            register_rest_route('api', '/export/csv', [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => function () {
                        do_action(export_articles_csv);
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('delete_posts');
                    }
                ]
            ]);

            /**
             * Pour récuperer les clients
             */
            register_rest_route('api', '/clients/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
                        $start  = isset($_REQUEST['length']) ? (int)$_REQUEST['start'] : 0;
                        $args = [
                            'number' => $length,
                            'offset' => $start,
                            'orderby' => 'registered',
                            'role__in' => ['fz-particular', 'fz-company'],
                            'order' => 'DESC'
                        ];

                        $user = wp_get_current_user(  );
                        if (\in_array('administrator', $user->roles)) {
                            if (!empty($_REQUEST['responsible'])) {
                                $args = array_merge($args, [
                                    'meta_query' => [
                                        [
                                            'key' => 'responsible',
                                            'value' => (int)$_REQUEST['responsible']
                                        ]
                                    ]
                                ]);
                            }
                        } else {
                            if (\in_array('editor', $user->roles)) {
                                $args = array_merge($args, [
                                    'meta_query' => [
                                        [
                                            'key' => 'responsible',
                                            'value' => $user->ID
                                        ]
                                    ]
                                ]);
                            }
                        }

                        $user_query = new \WP_User_Query($args);
                        if (!empty($user_query->get_results())) {
                            $results = [];
                            $request = new \WP_REST_Request();
                            $request->set_param('context', 'edit');

                            foreach ( $user_query->get_results() as $user ) {
                                $user_controller = new \WC_REST_Customers_V2_Controller();
                                $customer = new \WP_User($user->ID);
                                $response = $user_controller->prepare_item_for_response($customer, $request);

                                // TODO: Ne pas afficher au commercial certain contenue du client
                                $results[] = $response->data;
                            }
                            return [
                                "recordsTotal" => $user_query->total_users,
                                "recordsFiltered" => $user_query->total_users,
                                'data' => $results
                            ];
                        } else {
                            return [
                                "recordsTotal" => 0,
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


            register_rest_route('api', '/faq-client/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        global $wpdb;
                        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
                        $start = isset($_REQUEST['length']) ? (int)$_REQUEST['start'] : 1;

                $your_articles_request = <<<SQL
SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts as pts
WHERE pts.post_type = "fz_faq_client" AND pts.post_status = "publish" 
LIMIT $length OFFSET $start
SQL;
                        $results = $wpdb->get_results($your_articles_request);
                        $count_sql = "SELECT FOUND_ROWS()";
                        $total = $wpdb->get_var($count_sql);
                        $articles = [];
                        foreach ($results as $result) {
                            $article_controller = new \WP_REST_Posts_Controller('fz_faq_client');
                            $post = get_post((int) $result->ID);
                            $response = $article_controller->prepare_item_for_response($post, new \WP_REST_Request());
                            $articles[] = $response->get_data();
                        }
                        $wpdb->flush();
                        return [
                            "recordsTotal" => $total,
                            "recordsFiltered" => $total,
                            'data' => $articles
                        ];
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ]
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
             * Envoyer un mail au client pour la demande de servise
             */
            register_rest_route('api', '/mail/sav/(?P<sav_id>\d+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function(\WP_REST_Request $rq) {
                        $params  = $_REQUEST;
                        $subject = stripslashes($params['subject']);
                        $message = stripslashes($params['message']);
                        $sender = (int)$params['sender'];
                        $mailing_id = (int)$params['mailing_id'];
                        $sav_id = (int)$rq['sav_id'];
                        do_action('fz_sav_contact_mail', $sav_id, $sender, $mailing_id, $subject, $message);
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => [
                        'sav_id' => [
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
                        // Marquer le fournisseur avec son date
                        update_user_meta($supplier_id, 'send_mail_review_date', date_i18n("Y-m-d H:i:s"));
                        do_action('fz_submit_articles_for_validation', $supplier_id, $subject, $content, $cc, $articles);
                    }
                ]
            ], false);


            register_rest_route('api', '/mail/user/(?P<user_id>\d+)', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        $user_id = (int) $rq['user_id'];
                        if (\is_nan($user_id)) wp_send_json_error("Identifiant introuvable");
                        $password = sanitize_text_field($_REQUEST['pwd']);
                        do_action("fz_mail_api_insert_user", $user_id, $password);
                    }
                ]
            ], false);

            register_rest_route('api', '/options', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => function (\WP_REST_Request $rq) {
                        // nif, stat, rc, & cif
                        foreach ($_REQUEST as $option => $value)
                            update_option($option, sanitize_text_field($value));
                        wp_send_json_success("Donnee mise a jour avec succes");
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('delete_posts');
                    },
                ],
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => function () {
                        $option_fields = ['nif', 'stat', 'rc', 'cif', 'bmoi'];
                        $results = [];
                        foreach ($option_fields as $field) {
                            $results[ $field ] = get_option($field, null);
                        }
                        wp_send_json($results);
                    }
                ]
            ], false);

        });

    }

    public function register_rest_user () {
        $metas = [
            'company_name',
            'address',
            'mail_commercial_cc',
            'mail_logistics_cc',
            'phone',
            'reference',
            'company_status', // dealer (Revendeur) or professional (Professionnel)
            'stat',
            'nif',
            'rc',
            'cif',
            'cin',
            'date_cin'
        ];
        $User = wp_get_current_user();
        $admin = in_array('administrator', $User->roles) ? 'administrator' : false;
        foreach ( $metas as $meta ) {
            register_rest_field('user', $meta, [
                'update_callback' => function ($value, $object, $field_name) use ($admin) {
                    $client_id = $object->ID;
                    $field_value = get_field($field_name, 'user_' . $object->ID);
                    if ($admin !== 'administrator' && $field_name === 'company_name') {
                        $current_user = new \WP_User($client_id);
                        if (\in_array('fz-company', $current_user->roles)) return $field_value;
                        return get_field('reference', 'user_' . $object['id']);
                    } else return update_field($field_name, $value, 'user_' . $client_id);

                },
                'get_callback' => function ($object, $field_name) use ($admin) {
                    $client_id = (int) $object['id'];
                    $field_value = get_field($field_name, 'user_' . $client_id);
                    if ($admin !== 'administrator' && $field_name === 'company_name') {
                        $current_user = new \WP_User($client_id);
                        if (\in_array('fz-company', $current_user->roles)) return $field_value;
                        return get_field('reference', 'user_' . $object['id']);
                    } else return $field_value;
                }
            ]);
        }

        /**
         * Variable utiliser seulement pour les comptes fournisseurs.
         * Cette meta est utiliser pour la dernnier date d'envoie au fournisseur la mise à jour
         * de son article
         */
        register_rest_field('user', 'send_mail_review_date', [
            'update_callback' => function ($value, $object, $field_name) use ($admin) {
            return update_user_meta($object->ID, $field_name, $value);

            },
            'get_callback' => function ($object, $field_name) use ($admin) {
                return get_user_meta($object['id'], $field_name, true);
            }
        ]);

        /**
         * Variable utiliser pour les clients seulement
         * Ce champ est pour les clients qui contient le commercial responsable
         */
        register_rest_field('user', 'responsible', [
            'update_callback' => function ($value, $object, $field_name) {
                return update_user_meta($object->ID, $field_name, $value);
            },
            'get_callback' => function ($object, $field_name) {
                $responsible = get_user_meta($object['id'], $field_name, true);
                return $responsible;
            }
        ]);

        // ce champ permet de désactiver ou activer un utilisateur
        // ja_disable_user (Plugin: JA_DisableUsers, url: http://jaredatchison.com)
        // valeur disponible: 0: Active, 1: Désactiver
        $disable_field_name = "ja_disable_user";
        register_rest_field('user', 'disable', [
            'update_callback' => function ($value, $object) use ($disable_field_name) {
                return update_user_meta($object->ID, $disable_field_name, $value);
            },
            'get_callback' => function ($object) use ($disable_field_name) {
                $responsible = get_user_meta($object['id'], $disable_field_name, true);
                return (int) $responsible;
            }
        ]);

        // Ce champ permet de mettre un utilisateur en attente ou publier
        // fz_pending_user. 0: Active, 1: Pending
        $pending_field_name = 'fz_pending_user';
        register_rest_field('user', 'pending', [
            'update_callback' => function ($value, $object) use ($pending_field_name) {
                return update_user_meta($object->ID, $pending_field_name, $value);
            },
            'get_callback' => function ($object) use ($pending_field_name) {
                /**
                 * Return
                 * (mixed) Will be an array if $single is false. Will be value of meta data field if $single is true.
                 */
                $result = get_user_meta($object['id'], $pending_field_name, true);
                return empty($result) ? 0 : intval($result);
            }
        ]);


    }

    public function register_rest_fz_product () {
        $metas = ['price', 'date_add', 'date_review', 'product_id', 'total_sales', 'user_id'];
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

        $article_metas = [
            ['name' => 'marge', 'key'        => '_fz_marge'], // (int)
            ['name' => 'marge_dealer', 'key' => '_fz_marge_dealer'], // (int)
            ['name' => 'marge_particular', 'key' => '_fz_marge_particular'], // (int)
        ];

        foreach ($article_metas as $meta) {
            register_rest_field('fz_product', $meta['name'], [
                'update_callback' => function ($value, $object) use ($meta) {
                    // $product_id = get_field('product_id', (int)$object->ID);
                    // $product = new \WC_Product((int)$product_id);
                    // $product->update_meta_data($meta['key'], $value);
                    return update_post_meta((int) $object->ID, $meta['key'], $value);
                },
                'get_callback' => function ($object) use ($meta) {
                    $meta_value = get_post_meta((int) $object['id'], $meta['key'], true);
                    
                    if (!$meta_value || is_null($meta_value)) {
                        // BUG FIX: Ici on corrige le bug que les marges doivent se trouver dans l'article mais pas dans les produits
                        $product_id = get_field('product_id', (int)$object['id']);
                        $product = new \WC_Product((int)$product_id);
                        $marge = $product->get_meta($meta['key']);

                        // Update post meta
                        update_post_meta((int) $object['id'], $meta['key'], $marge);
                        $meta_value = $marge;
                    }
                    return $meta_value;
                }
            ]);
        }

        // @type string Out/In
        register_rest_field('fz_product', 'garentee', [
            'update_callback' => function ($value, $object) {
                return update_post_meta( (int)$object->ID, '_fz_garentee', $value );
            },
            'get_callback' => function ($object, $field_name) {
                $garentee = get_post_meta( (int) $object['id'], '_fz_garentee', true );
                return $garentee;
            }
        ]);

        register_rest_field('fz_product', 'product_status', [
            'update_callback' => function ($value, $object) {
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

        /***
         * Cette variable est requis pour l'administrateur
         * Disponible - 0, Rupture -1, Obsolete - 2, et Commande - 3
         */
        register_rest_field('fz_product', 'condition', [
            'update_callback' => function ($value, $object) {
                return update_post_meta( (int)$object->ID, '_fz_condition', $value );
            },
            'get_callback' => function ($object, $field_name) {
                $condition = get_post_meta( (int) $object['id'], '_fz_condition', true );
                return (int) $condition;
            }
        ]);

        register_rest_field('fz_product', 'product_thumbnail', [
            'get_callback' => function ($object) {
                $product_id = get_field('product_id', (int)$object['id']);
                $product_controller = new \WC_REST_Products_V2_Controller();
                $request = new \WP_REST_Request();
                $request->set_param('context', 'edit');
                $product_response = $product_controller->prepare_object_for_response(new \WC_Product((int)$product_id), $request);
                $images = $product_response->data['images'];
                return is_array($images) && !empty($images) ? $images[0] : ['src' => 0, 'src' => ''];
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

    public function register_rest_faq_client() {
        $post_type = 'fz_faq_client';
        $metas = ['faq_category']; // 1: Profesional, 2: Particular
        foreach ($metas as $meta) {
            register_rest_field($post_type, $meta, [
                'update_callback' => function ($value, $object, $field_name) {
                    return update_post_meta($object->ID, $field_name, $value);
        
                },
                'get_callback' => function ($object, $field_name) {
                    $ctg = get_post_meta( (int) $object['id'], $field_name, true);
                    return $ctg ? $ctg : null;
                }
            ]);
        }
    }

    public function register_rest_order () {
        $post_types = wc_get_order_types();
        foreach ( $post_types as $type ) {
            // ACF field
            foreach ( ['user_id', 'position'] as $meta ) {
                register_rest_field($type, $meta, [
                    'update_callback' => function ($value, $object, $field_name) {
                        return update_field($field_name, $value, (int)$object->ID);
                    },
                    'get_callback' => function ($object, $field_name) {
                        return get_field($field_name, (int)$object['id']);
                    }
                ]);
            }

            $params = $_REQUEST;
            // Envoyer comme reponse si la requete a pour context une edition
            if (isset($params['context']) && $params['context'] === "edit") {
                register_rest_field($type, 'customer_data', [
                    'get_callback' => function ($object, $field_name) {
                        $order =  new \WC_Order( (int)$object['id'] );

                        $usr_controller = new \WP_REST_Users_Controller();
                        $request = new \WP_REST_Request();
                        $request->set_param('context', 'edit');
                        $cs_response = $usr_controller->prepare_item_for_response(new \WP_User($order->get_customer_id()), $request);
                        
                        return $cs_response->data;
                    }
                ]);
            }

            // post meta field
            // @var date_send: Format YYYY-MM-DD HH:mm:ss
            foreach (['client_role', 'date_send', 'line_items_zero'] as $meta) {
                register_rest_field($type, $meta, [
                    'update_callback' => function ($value, $object, $field_name) {
                        return update_post_meta( (int)$object->ID, $field_name, $value );
                    },
                    'get_callback' => function ($object, $field_name) {
                        $value = get_post_meta( (int) $object['id'], $field_name, true );
                        if ('line_items_zero' === $field_name) {
                            if (!$value) return [];
                            return $value;
                        } else {
                            return $value ? $value : null;
                        }
                    }
                ]);
            }
            

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
